<?php

namespace App\Services;

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

    public function __construct(private SppPaymentService $payments) {}

    public function preview(string $path, ?string $sourceName = null): array
    {
        return $this->process($path, false, $sourceName);
    }

    public function import(string $path, ?string $sourceName = null): array
    {
        return $this->process($path, true, $sourceName);
    }

    private function process(string $path, bool $persist, ?string $sourceName): array
    {
        [$headers, $rows] = $this->readRows($path);
        $result = ['total' => count($rows), 'valid' => 0, 'imported' => 0, 'duplicates' => 0, 'failures' => [], 'rows' => []];
        $prepared = [];

        if (! $persist) {
            DB::beginTransaction();
        }

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
            $left['nis'], $left['year'], $left['month'], $left['transaction_at'],
        ] <=> [
            $right['nis'], $right['year'], $right['month'], $right['transaction_at'],
        ]);

        foreach ($prepared as $row) {
            if (SppPayment::whereIn('import_key', [$row['import_key'], $row['legacy_import_key']])->exists()) {
                $row['status'] = 'Duplikat';
                $row['message'] = 'Transaksi ini sudah pernah diimpor.';
                $result['duplicates']++;
                $result['rows'][] = $row;
                continue;
            }

            $student = Student::with('schoolClass.educationUnit')
                ->where('nis', $row['nis'])
                ->get()
                ->first(fn (Student $candidate) => $this->matchesUnit($candidate, $row['unit']));
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

                $this->payments->quote($student, $row['year'], [$row['month']]);
                $this->payments->record($student, $data);
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

        return $result;
        } finally {
            if (! $persist && DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
    }

    private function readRows(string $path): array
    {
        $rows = StudentXlsx::read($path);
        $headerIndex = collect($rows)->search(fn (array $row) => in_array('nis', array_map($this->normalizeHeader(...), $row), true));
        if ($headerIndex === false) {
            throw ValidationException::withMessages(['file' => 'Header NIS tidak ditemukan pada file Excel.']);
        }

        $headers = array_map($this->normalizeHeader(...), $rows[$headerIndex]);
        $required = ['nis', 'nama', 'jenis_pendidikan', 'cara_bayar', 'bulan', 'tahun', 'waktu', 'nominal'];
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

    private function prepareRow(int $line, array $values, array $headers): array
    {
        $row = array_combine($headers, array_slice(array_pad($values, count($headers), null), 0, count($headers)));
        $nis = trim((string) ($row['nis'] ?? ''));
        $name = trim((string) ($row['nama'] ?? ''));
        $unit = trim((string) ($row['jenis_pendidikan'] ?? ''));
        $monthName = strtolower(trim((string) ($row['bulan'] ?? '')));
        $year = filter_var($row['tahun'] ?? null, FILTER_VALIDATE_INT);
        $nominal = $this->normalizeNominal($row['nominal'] ?? null);
        $method = strtolower(trim((string) ($row['cara_bayar'] ?? '')));
        $transactionAt = $this->normalizeDateTime($row['waktu'] ?? null);

        $base = ['line' => $line, 'nis' => $nis, 'name' => $name, 'unit' => $unit, 'month_name' => $monthName, 'year' => (int) $year, 'nominal' => $nominal];
        $error = match (true) {
            $nis === '' => 'NIS kosong.',
            $name === '' => 'Nama siswa kosong.',
            $unit === '' => 'Jenis pendidikan kosong.',
            ! isset(self::MONTHS[$monthName]) => 'Nama bulan tidak valid.',
            ! $year || $year < 2000 || $year > 2100 => 'Tahun tidak valid.',
            $nominal < 1 => 'Nominal harus lebih dari nol.',
            ! in_array($method, ['cash', 'transfer'], true) => 'Cara bayar harus cash atau transfer.',
            $transactionAt === null => 'Waktu transaksi tidak valid.',
            default => null,
        };

        if ($error) {
            return $base + ['status' => 'Gagal', 'message' => $error, 'error' => true];
        }

        $month = self::MONTHS[$monthName];
        $paymentMethod = ucfirst($method);
        $operator = trim((string) ($row['petugas'] ?? ''));
        $importKey = hash('sha256', implode('|', [$unit, $nis, $year, $month, $transactionAt, $nominal, $paymentMethod]));

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

        return preg_replace('/_+/', '_', $header);
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
