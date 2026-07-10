<?php

namespace App\Services;

use App\Models\FeeType;
use App\Models\OtherPayment;
use App\Models\Student;
use App\Support\ClassLevel;
use App\Support\StudentXlsx;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class OtherPaymentImportService
{
    public function __construct(
        private OtherPaymentService $payments,
        private LaundryPaymentService $laundryPayments,
    ) {}

    public function sources(string $path, ?string $paymentGroup = null): array
    {
        [$headers, $rows] = $this->readRows($path);
        $sources = [];
        $feeTypes = FeeType::with(['educationUnit', 'schoolClass'])
            ->where('is_active', true)
            ->when($paymentGroup, fn ($query, $group) => $query->paymentGroup($group))
            ->orderBy('name')
            ->get();

        foreach ($rows as $row) {
            $values = $this->combine($row['values'], $headers);
            $category = trim((string) ($values['kategori_pembayaran'] ?? ''));
            $unit = trim((string) ($values['jenis_pendidikan'] ?? ''));
            $class = trim((string) ($values['kelas'] ?? ''));
            $classLevel = ClassLevel::key($class);
            $key = $this->sourceKey($category, $unit, $classLevel);

            if (! isset($sources[$key])) {
                $suggestion = $this->suggestFeeType($feeTypes, $category, $unit, $classLevel);
                $sources[$key] = [
                    'key' => $key,
                    'category' => $category,
                    'unit' => $unit,
                    'class_level' => $classLevel,
                    'rows' => 0,
                    'suggested_fee_type_id' => $suggestion?->id,
                ];
            }
            $sources[$key]['rows']++;
        }

        return array_values($sources);
    }

    public function preview(string $path, array $mappings, ?string $sourceName = null, ?string $paymentGroup = null): array
    {
        return $this->process($path, $mappings, false, $sourceName, $paymentGroup);
    }

    public function import(string $path, array $mappings, ?string $sourceName = null, ?string $paymentGroup = null): array
    {
        return $this->process($path, $mappings, true, $sourceName, $paymentGroup);
    }

    private function process(string $path, array $mappings, bool $persist, ?string $sourceName, ?string $paymentGroup): array
    {
        [$headers, $rows] = $this->readRows($path);
        $result = ['total' => count($rows), 'valid' => 0, 'imported' => 0, 'duplicates' => 0, 'failures' => [], 'rows' => []];
        $prepared = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $row) {
                $parsed = $this->prepareRow($row['line'], $row['values'], $headers);
                if (isset($parsed['error'])) {
                    $result['failures'][] = $parsed;
                    $result['rows'][] = $parsed;

                    continue;
                }
                $prepared[] = $parsed;
            }

            usort($prepared, fn (array $left, array $right) => [
                $left['nis'], $left['category'], $left['transaction_at'],
            ] <=> [
                $right['nis'], $right['category'], $right['transaction_at'],
            ]);

            $existingImportKeys = $this->existingImportKeys($prepared);
            $studentsByNis = Student::with('schoolClass.educationUnit')
                ->whereIn('nis', array_values(array_unique(array_column($prepared, 'nis'))))
                ->get()
                ->groupBy(fn (Student $student) => (string) $student->nis);
            $feeTypes = FeeType::with(['educationUnit', 'schoolClass', 'academicYear'])
                ->when($paymentGroup, fn ($query, $group) => $query->paymentGroup($group))
                ->where('is_active', true)
                ->get();
            $feeTypesById = $feeTypes
                ->whereIn('id', collect($mappings)->filter()->map(fn ($id) => (int) $id)->unique()->all())
                ->keyBy('id');

            foreach ($prepared as $row) {
                if (isset($existingImportKeys[$row['import_key']])) {
                    $row['status'] = 'Duplikat';
                    $row['message'] = 'Transaksi ini sudah pernah diimpor.';
                    $result['duplicates']++;
                    $result['rows'][] = $row;

                    continue;
                }

                $student = $studentsByNis->get($row['nis'], collect())
                    ->first(fn (Student $candidate) => $this->matchesUnit($candidate, $row['unit']));
                if (! $student) {
                    $this->fail($result, $row, "NIS {$row['nis']} tidak ditemukan. Unit: {$row['unit']}.");

                    continue;
                }
                if ($this->normalizeLookup($student->name) !== $this->normalizeLookup($row['name'])) {
                    $this->fail($result, $row, "Nama pada Excel tidak cocok dengan siswa NIS {$row['nis']}.");

                    continue;
                }
                $feeTypeId = $mappings[$row['source_key']] ?? null;
                $mappedFeeType = $feeTypeId ? $feeTypesById->get((int) $feeTypeId) : null;
                $feeType = $this->feeTypeForRow($feeTypes, $row, $student, $mappedFeeType);
                if (! $feeType) {
                    $this->fail($result, $row, 'Kategori pembayaran belum cocok dengan unit atau kelas siswa.');

                    continue;
                }

                try {
                    $data = [
                        'transaction_date' => substr($row['transaction_at'], 0, 10),
                        'transaction_time' => substr($row['transaction_at'], 11, 8),
                        'payment_method' => $row['payment_method'],
                        'status' => 'Diterima',
                        'paid_amount' => $row['nominal'],
                        'operator_name' => $row['operator_name'],
                        'import_source' => $sourceName ?? basename($path),
                        'import_key' => $row['import_key'],
                    ];

                    DB::transaction(function () use ($paymentGroup, $row, $student, $feeType, $data): void {
                        if ($paymentGroup === 'laundry') {
                            $transactionDate = CarbonImmutable::parse($row['transaction_at']);
                            $this->laundryPayments->record($student, $feeType, $data + [
                                'year' => $transactionDate->year,
                                'months' => [$transactionDate->month],
                            ]);

                            return;
                        }

                        $this->payments->record($student, $feeType, $data);
                    });
                    $existingImportKeys[$row['import_key']] = true;
                    if ($persist) {
                        $result['imported']++;
                    }

                    $row['mapped_category'] = $feeType->name;
                    $row['status'] = 'Valid';
                    $row['message'] = $persist ? 'Berhasil diimpor.' : 'Siap diimpor.';
                    $result['valid']++;
                    $result['rows'][] = $row;
                } catch (ValidationException $exception) {
                    $this->fail($result, $row, collect($exception->errors())->flatten()->first() ?? $exception->getMessage());
                } catch (Throwable $exception) {
                    $this->fail($result, $row, 'Transaksi tidak dapat diproses: '.$exception->getMessage());
                }
            }

            usort($result['rows'], fn (array $left, array $right) => $left['line'] <=> $right['line']);

            if ($persist) {
                DB::commit();
            }

            return $result;
        } catch (Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $exception;
        } finally {
            if (! $persist && DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
    }

    private function existingImportKeys(array $rows): array
    {
        return collect($rows)
            ->pluck('import_key')
            ->unique()
            ->chunk(500)
            ->flatMap(fn ($chunk) => OtherPayment::whereIn('import_key', $chunk)->pluck('import_key'))
            ->flip()
            ->all();
    }

    private function readRows(string $path): array
    {
        $rows = StudentXlsx::read($path);
        $headerIndex = collect($rows)->search(fn (array $row) => in_array('nis', array_map($this->normalizeHeader(...), $row), true));
        if ($headerIndex === false) {
            throw ValidationException::withMessages(['file' => 'Header NIS tidak ditemukan pada file Excel.']);
        }

        $headers = array_map($this->normalizeHeader(...), $rows[$headerIndex]);
        $required = ['nis', 'nama', 'kategori_pembayaran', 'jenis_pendidikan', 'cara_bayar', 'nominal', 'waktu'];
        if ($missing = array_diff($required, $headers)) {
            throw ValidationException::withMessages(['file' => 'Kolom wajib belum tersedia: '.implode(', ', $missing).'.']);
        }

        $data = [];
        foreach (array_slice($rows, $headerIndex + 1) as $offset => $values) {
            if (array_filter($values, fn ($value) => trim((string) $value) !== '')) {
                $data[] = ['line' => $headerIndex + $offset + 2, 'values' => $values];
            }
        }

        return [$headers, $data];
    }

    private function prepareRow(int $line, array $values, array $headers): array
    {
        $row = $this->combine($values, $headers);
        $nis = trim((string) ($row['nis'] ?? ''));
        $name = trim((string) ($row['nama'] ?? ''));
        $category = trim((string) ($row['kategori_pembayaran'] ?? ''));
        $unit = trim((string) ($row['jenis_pendidikan'] ?? ''));
        $class = trim((string) ($row['kelas'] ?? ''));
        $nominal = (int) preg_replace('/[^\d]/', '', (string) ($row['nominal'] ?? ''));
        $method = strtolower(trim((string) ($row['cara_bayar'] ?? '')));
        $transactionAt = $this->normalizeDateTime($row['waktu'] ?? null);
        $base = compact('line', 'nis', 'name', 'category', 'unit', 'class', 'nominal');

        $error = match (true) {
            $nis === '' => 'NIS kosong.',
            $name === '' => 'Nama siswa kosong.',
            $category === '' => 'Kategori pembayaran kosong.',
            $unit === '' => 'Unit pendidikan kosong.',
            $nominal < 1 => 'Nominal harus lebih dari nol.',
            ! in_array($method, ['cash', 'transfer'], true) => 'Cara bayar harus cash atau transfer.',
            $transactionAt === null => 'Waktu transaksi tidak valid.',
            default => null,
        };
        if ($error) {
            return $base + ['status' => 'Gagal', 'message' => $error, 'error' => true];
        }

        $paymentMethod = ucfirst($method);
        $operator = trim((string) ($row['petugas'] ?? ''));

        return $base + [
            'source_key' => $this->sourceKey($category, $unit, ClassLevel::key($class)),
            'payment_method' => $paymentMethod,
            'transaction_at' => $transactionAt,
            'operator_name' => $operator !== '' ? $operator : null,
            'import_key' => hash('sha256', implode('|', [$nis, $category, $unit, $transactionAt, $nominal, $paymentMethod])),
        ];
    }

    private function combine(array $values, array $headers): array
    {
        return array_combine($headers, array_slice(array_pad($values, count($headers), null), 0, count($headers)));
    }

    private function fail(array &$result, array $row, string $message): void
    {
        $row['status'] = 'Gagal';
        $row['message'] = $message;
        $result['failures'][] = $row;
        $result['rows'][] = $row;
    }

    private function sourceKey(string $category, string $unit, ?string $classLevel): string
    {
        return hash('sha256', $this->normalizeCategory($category).'|'.$this->normalizeLookup($unit).'|'.$classLevel);
    }

    private function matchesUnit(Student $student, string $unit): bool
    {
        $educationUnit = $student->schoolClass?->educationUnit;
        $normalized = $this->normalizeLookup($unit);

        return $educationUnit
            && in_array($normalized, [
                $this->normalizeLookup($educationUnit->code),
                $this->normalizeLookup($educationUnit->name),
            ], true);
    }

    private function normalizeHeader(string $header): string
    {
        $header = strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $header)));
        $header = str_replace(['.', '/', ' '], ['', '_', '_'], $header);

        $header = preg_replace('/_+/', '_', $header);

        return $header === 'unit_pendidikan' ? 'jenis_pendidikan' : $header;
    }

    private function normalizeDateTime(mixed $value): ?string
    {
        $value = trim((string) $value);
        foreach (['Y-m-d H:i:s', 'Y-m-d H:i', 'd/m/Y H:i:s', 'd/m/Y H:i'] as $format) {
            $date = CarbonImmutable::createFromFormat('!'.$format, $value);
            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        return null;
    }

    private function normalizeCategory(string $value): string
    {
        $value = preg_replace('/\bT\.?\s*A\.?\s*\.?\s*20\d{2}\s*[\/-]\s*20\d{2}\b/iu', '', $value);
        $value = preg_replace('/\bT(?:AHUN)?\s*(?:PELAJARAN|AJARAN)\s*20\d{2}\s*[\/-]\s*20\d{2}\b/iu', '', $value);
        $value = preg_replace('/\b20\d{2}\s*[\/-]\s*20\d{2}\b/iu', '', $value);
        $value = preg_replace('/\b1[34]\d{2}(?:\s*[\/-]\s*1[34]\d{2})?\s*H\b/iu', '', $value);

        return preg_replace('/h$/', '', $this->normalizeLookup($value));
    }

    private function normalizeLookup(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(trim($value)));
    }

    private function suggestFeeType($feeTypes, string $category, string $unit, ?string $classLevel): ?FeeType
    {
        $academicYear = $this->academicYearFromCategory($category);

        return $feeTypes
            ->filter(fn (FeeType $feeType) => $this->feeTypeMatchesCategory($feeType, $category)
                && $this->feeTypeMatchesUnit($feeType, $unit)
                && $this->feeTypeMatchesClassLevel($feeType, $classLevel))
            ->sortBy(fn (FeeType $feeType) => $this->feeTypeScore($feeType, $academicYear, $classLevel))
            ->first();
    }

    private function feeTypeForRow($feeTypes, array $row, Student $student, ?FeeType $mappedFeeType): ?FeeType
    {
        $student->loadMissing('schoolClass.educationUnit');

        if ($mappedFeeType?->matchesSchoolClass($student->schoolClass)) {
            return $mappedFeeType;
        }

        $academicYear = $this->academicYearFromCategory($row['category']);
        $classLevel = ClassLevel::key($row['class'] ?? '');

        return $feeTypes
            ->filter(fn (FeeType $feeType) => $this->feeTypeMatchesCategory($feeType, $row['category'])
                && $feeType->matchesSchoolClass($student->schoolClass))
            ->sortBy(fn (FeeType $feeType) => $this->feeTypeScore($feeType, $academicYear, $classLevel))
            ->first();
    }

    private function feeTypeMatchesCategory(FeeType $feeType, string $category): bool
    {
        return $this->normalizeCategory($feeType->name) === $this->normalizeCategory($category);
    }

    private function feeTypeMatchesUnit(FeeType $feeType, string $unit): bool
    {
        return in_array($this->normalizeLookup($unit), [
            $this->normalizeLookup($feeType->educationUnit?->code ?? ''),
            $this->normalizeLookup($feeType->educationUnit?->name ?? ''),
        ], true);
    }

    private function feeTypeMatchesClassLevel(FeeType $feeType, ?string $classLevel): bool
    {
        if ($feeType->school_class_id !== null) {
            return ClassLevel::key($feeType->schoolClass?->level ?: $feeType->schoolClass?->name) === $classLevel;
        }

        return $feeType->class_level === null || $feeType->class_level === $classLevel;
    }

    private function feeTypeScore(FeeType $feeType, ?string $academicYear, ?string $classLevel): int
    {
        $yearScore = $academicYear && $feeType->academicYear
            ? ($this->normalizeLookup($feeType->academicYear->name) === $this->normalizeLookup($academicYear) ? 0 : 100)
            : ($feeType->academic_year_id ? 10 : 20);
        $classScore = match (true) {
            $feeType->school_class_id !== null => 0,
            $feeType->class_level !== null && $feeType->class_level === $classLevel => 1,
            $feeType->class_level === null => 2,
            default => 50,
        };

        return $yearScore + $classScore;
    }

    private function academicYearFromCategory(string $category): ?string
    {
        if (preg_match('/\b(20\d{2}\s*[\/-]\s*20\d{2})\b/u', $category, $matches)) {
            return preg_replace('/\s+/', '', str_replace('-', '/', $matches[1]));
        }

        return null;
    }
}
