<?php

namespace App\Services;

use App\Models\EducationUnit;
use App\Models\SppPayment;
use App\Models\Student;
use App\Support\StudentXlsx;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SppPaymentImportService
{
    private const MONTHS = [
        'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
        'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
        'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
    ];

    private const MONTH_LABELS = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function __construct(private SppPaymentService $payments) {}

    public function preview(string $path, ?string $sourceName = null, array $context = []): array
    {
        return $this->process($path, false, $sourceName, $context);
    }

    public function import(string $path, ?string $sourceName = null, array $context = []): array
    {
        return $this->process($path, true, $sourceName, $context);
    }

    private function process(string $path, bool $persist, ?string $sourceName, array $context): array
    {
        $context = $this->normalizeContext($context);
        [$headers, $rows] = $this->readRows($path);
        $result = ['total' => count($rows), 'valid' => 0, 'imported' => 0, 'duplicates' => 0, 'failures' => [], 'rows' => []];
        $prepared = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $row) {
                $parsed = $this->prepareRow($row['line'], $row['values'], $headers, $context);
                if (isset($parsed['error'])) {
                    $result['failures'][] = $parsed;
                    $result['rows'][] = $parsed;

                    continue;
                }
                $prepared[] = $parsed;
            }

            usort($prepared, fn (array $left, array $right) => [
                $left['nis'], $left['year'], $left['month'], $left['transaction_at'],
            ] <=> [
                $right['nis'], $right['year'], $right['month'], $right['transaction_at'],
            ]);

            $existingImportKeys = $this->existingImportKeys($prepared);
            $studentsByNis = Student::with(['schoolClass.educationUnit', 'academicYear'])
                ->whereIn('nis', array_values(array_unique(array_column($prepared, 'nis'))))
                ->get()
                ->groupBy(fn (Student $student) => (string) $student->nis);

            foreach ($prepared as $row) {
                if (isset($existingImportKeys[$row['import_key']]) || isset($existingImportKeys[$row['legacy_import_key']])) {
                    $row['status'] = 'Duplikat';
                    $row['message'] = 'Transaksi ini sudah pernah diimpor.';
                    $result['duplicates']++;
                    $result['rows'][] = $row;

                    continue;
                }

                $student = $studentsByNis->get($row['nis'], collect())
                    ->first(fn (Student $candidate) => (int) $candidate->schoolClass?->education_unit_id === $row['unit_id']);
                if (! $student) {
                    $row['status'] = 'Gagal';
                    $row['message'] = "NIS {$row['nis']} tidak ditemukan. Unit: {$row['unit']}.";
                    $result['failures'][] = $row;
                    $result['rows'][] = $row;

                    continue;
                }

                if ($this->normalizeLookup($student->name) !== $this->normalizeLookup($row['name'])) {
                    $row['status'] = 'Gagal';
                    $row['message'] = "Nama pada Excel tidak cocok dengan siswa NIS {$row['nis']}.";
                    $result['failures'][] = $row;
                    $result['rows'][] = $row;

                    continue;
                }
                if ($message = $this->sequentialImportWarning($student, $row)) {
                    $row['status'] = 'Gagal';
                    $row['message'] = $message;
                    $result['failures'][] = $row;
                    $result['rows'][] = $row;

                    continue;
                }

                try {
                    $data = [
                        'transaction_date' => substr($row['transaction_at'], 0, 10),
                        'transaction_time' => substr($row['transaction_at'], 11, 8),
                        'year' => $row['year'],
                        'months' => [$row['month']],
                        'payment_method' => $row['payment_method'],
                        'status' => 'Diterima',
                        'paid_amount' => $row['nominal'],
                        'operator_name' => $row['operator_name'],
                        'import_source' => $sourceName ?? basename($path),
                        'import_key' => $row['import_key'],
                    ];

                    $this->payments->recordImportedMonth($student, $data, $persist);
                    $existingImportKeys[$row['import_key']] = true;
                    if ($persist) {
                        $result['imported']++;
                    }

                    $row['status'] = 'Valid';
                    $row['message'] = $persist ? 'Berhasil diimpor.' : 'Siap diimpor.';
                    $result['valid']++;
                    $result['rows'][] = $row;
                } catch (ValidationException $exception) {
                    $row['status'] = 'Gagal';
                    $row['message'] = collect($exception->errors())->flatten()->first() ?? $exception->getMessage();
                    $result['failures'][] = $row;
                    $result['rows'][] = $row;
                } catch (Throwable $exception) {
                    $row['status'] = 'Gagal';
                    $row['message'] = 'Transaksi tidak dapat diproses: '.$exception->getMessage();
                    $result['failures'][] = $row;
                    $result['rows'][] = $row;
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

    private function normalizeContext(array $context): array
    {
        $unit = EducationUnit::find($context['unit_id'] ?? null);
        $month = filter_var($context['month'] ?? null, FILTER_VALIDATE_INT);
        $year = filter_var($context['year'] ?? null, FILTER_VALIDATE_INT);

        if (! $unit) {
            throw ValidationException::withMessages(['unit_id' => 'Unit pendidikan import wajib dipilih.']);
        }
        if (! $month || ! isset(self::MONTH_LABELS[$month])) {
            throw ValidationException::withMessages(['month' => 'Bulan import wajib dipilih.']);
        }
        if (! $year || $year < 2000 || $year > 2100) {
            throw ValidationException::withMessages(['year' => 'Tahun import tidak valid.']);
        }

        return [
            'unit_id' => (int) $unit->id,
            'unit_code' => (string) $unit->code,
            'unit_name' => (string) $unit->name,
            'unit_lookup' => array_filter([
                $this->normalizeLookup((string) $unit->code),
                $this->normalizeLookup((string) $unit->name),
            ]),
            'month' => (int) $month,
            'month_name' => strtolower(self::MONTH_LABELS[$month]),
            'year' => (int) $year,
        ];
    }

    private function existingImportKeys(array $rows): array
    {
        $keys = collect($rows)
            ->flatMap(fn (array $row) => [$row['import_key'], $row['legacy_import_key']])
            ->unique()
            ->values();

        return $keys
            ->chunk(500)
            ->flatMap(fn ($chunk) => SppPayment::whereIn('import_key', $chunk)->pluck('import_key'))
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
        $required = ['nis', 'nama', 'cara_bayar', 'waktu', 'nominal'];
        if ($missing = array_diff($required, $headers)) {
            throw ValidationException::withMessages(['file' => 'Kolom wajib belum tersedia: '.implode(', ', $missing).'.']);
        }

        $data = [];
        foreach (array_slice($rows, $headerIndex + 1) as $offset => $values) {
            if (! array_filter($values, fn ($value) => trim((string) $value) !== '')) {
                continue;
            }
            $data[] = ['line' => $headerIndex + $offset + 2, 'values' => $values];
        }

        return [$headers, $data];
    }

    private function prepareRow(int $line, array $values, array $headers, array $context): array
    {
        $row = array_combine($headers, array_slice(array_pad($values, count($headers), null), 0, count($headers)));
        $nis = trim((string) ($row['nis'] ?? ''));
        $name = trim((string) ($row['nama'] ?? ''));
        $unit = $context['unit_name'] ?: $context['unit_code'];
        $excelUnit = trim((string) ($row['jenis_pendidikan'] ?? ''));
        $excelMonth = $this->monthNumber($row['bulan'] ?? null);
        $excelYear = $this->optionalYear($row['tahun'] ?? null);
        $month = $context['month'];
        $monthName = $context['month_name'];
        $year = $context['year'];
        $nominal = $this->normalizeNominal($row['nominal'] ?? null);
        $method = strtolower(trim((string) ($row['cara_bayar'] ?? '')));
        $transactionAt = $this->normalizeDateTime($row['waktu'] ?? null);

        $base = ['line' => $line, 'nis' => $nis, 'name' => $name, 'unit' => $unit, 'unit_id' => $context['unit_id'], 'month_name' => $monthName, 'year' => (int) $year, 'nominal' => $nominal];
        $error = match (true) {
            $nis === '' => 'NIS kosong.',
            $name === '' => 'Nama siswa kosong.',
            $excelUnit !== '' && ! in_array($this->normalizeLookup($excelUnit), $context['unit_lookup'], true) => "Unit pada Excel ({$excelUnit}) tidak sesuai dengan pilihan form ({$unit}).",
            array_key_exists('bulan', $row) && trim((string) ($row['bulan'] ?? '')) !== '' && $excelMonth === null => 'Nama bulan tidak valid.',
            $excelMonth !== null && $excelMonth !== $month => 'Bulan pada Excel tidak sesuai dengan pilihan form.',
            array_key_exists('tahun', $row) && trim((string) ($row['tahun'] ?? '')) !== '' && $excelYear === null => 'Tahun tidak valid.',
            $excelYear !== null && $excelYear !== $year => 'Tahun pada Excel tidak sesuai dengan pilihan form.',
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
        $importKey = hash('sha256', implode('|', [$context['unit_code'], $nis, $year, $month, $transactionAt, $nominal, $paymentMethod]));

        return $base + [
            'month' => $month,
            'payment_method' => $paymentMethod,
            'transaction_at' => $transactionAt,
            'operator_name' => $operator !== '' ? $operator : null,
            'import_key' => $importKey,
            'legacy_import_key' => hash('sha256', implode('|', [$nis, $year, $month, $transactionAt, $nominal, $paymentMethod])),
        ];
    }

    private function normalizeHeader(string $header): string
    {
        $header = strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $header)));
        $header = str_replace(['.', '/', ' '], ['', '_', '_'], $header);

        $header = preg_replace('/_+/', '_', $header);

        return $header === 'unit_pendidikan' ? 'jenis_pendidikan' : $header;
    }

    private function monthNumber(mixed $value): ?int
    {
        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return null;
        }

        if (isset(self::MONTHS[$value])) {
            return self::MONTHS[$value];
        }

        $number = filter_var($value, FILTER_VALIDATE_INT);

        return $number && isset(self::MONTH_LABELS[$number]) ? (int) $number : null;
    }

    private function optionalYear(mixed $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $year = filter_var($value, FILTER_VALIDATE_INT);

        return $year && $year >= 2000 && $year <= 2100 ? (int) $year : null;
    }

    private function normalizeNominal(mixed $value): int
    {
        return (int) preg_replace('/[^\d]/', '', (string) $value);
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

    private function sequentialImportWarning(Student $student, array $row): ?string
    {
        $oldest = $this->payments->oldestOutstandingPeriod($student);
        if (! $oldest) {
            return null;
        }

        $expectedPeriod = ((int) $oldest['year'] * 100) + (int) $oldest['month'];
        $importPeriod = ((int) $row['year'] * 100) + (int) $row['month'];
        if ($importPeriod <= $expectedPeriod) {
            return null;
        }

        return 'Import SPP harus berurutan. Siswa ini harus diimpor bulan '
            .$oldest['month_name'].' '.$oldest['year']
            .' terlebih dahulu sebelum '.$this->monthLabel((int) $row['month']).' '.$row['year']
            .'. Untuk MTs dan MA, Juli 2025 dilewati karena termasuk Daftar Ulang.';
    }

    private function monthLabel(int $month): string
    {
        return self::MONTH_LABELS[$month] ?? 'Bulan';
    }

    private function normalizeLookup(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(trim($value)));
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
}
