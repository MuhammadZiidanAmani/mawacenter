<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\EducationUnit;
use App\Models\Student;
use App\Support\StudentXlsx;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class StudentImportService
{
    public function preview(string $path, AcademicYear $activeYear): array
    {
        return $this->process($path, $activeYear, false);
    }

    public function import(string $path, AcademicYear $activeYear): array
    {
        return $this->process($path, $activeYear, true);
    }

    private function process(string $path, AcademicYear $activeYear, bool $persist): array
    {
        [$headers, $rows] = $this->readRows($path);
        $result = [
            'total' => count($rows),
            'valid' => 0,
            'imported' => 0,
            'duplicates' => 0,
            'created_classes' => 0,
            'failures' => [],
            'rows' => [],
        ];

        DB::beginTransaction();

        try {
            $units = EducationUnit::with('schoolClasses')->get();

            foreach ($rows as $sourceRow) {
                $row = $this->prepareRow($sourceRow['line'], $sourceRow['values'], $headers);
                if (isset($row['error'])) {
                    $this->fail($result, $row, $row['message']);
                    continue;
                }

                $unit = $units->first(fn (EducationUnit $item) => $this->matchesUnit($item, $row['unit']));
                if (! $unit) {
                    $this->fail($result, $row, "Unit Pendidikan \"{$row['unit']}\" tidak ditemukan.");
                    continue;
                }

                $row['unit'] = $unit->code;
                $existing = Student::where('nis', $row['nis'])
                    ->whereHas('schoolClass', fn ($query) => $query->where('education_unit_id', $unit->id))
                    ->exists();
                if ($existing) {
                    $row['status'] = 'Duplikat';
                    $row['message'] = "NIS {$row['nis']} sudah digunakan pada unit {$unit->code}.";
                    $result['duplicates']++;
                    $result['rows'][] = $row;
                    continue;
                }

                if ($row['nisn'] !== null && Student::where('nisn', $row['nisn'])->exists()) {
                    $this->fail($result, $row, "NISN {$row['nisn']} sudah digunakan.");
                    continue;
                }

                $class = $unit->schoolClasses->first(
                    fn ($item) => $this->normalizeLookup($item->name) === $this->normalizeLookup($row['class'])
                );
                if (! $class) {
                    $class = $unit->schoolClasses()->create([
                        'name' => $row['class'],
                        'level' => 'Kelas '.$row['class'],
                    ]);
                    $unit->schoolClasses->push($class);
                    $result['created_classes']++;
                }

                try {
                    Student::create([
                        'nis' => $row['nis'],
                        'nisn' => $row['nisn'],
                        'name' => $row['name'],
                        'birth_place' => $row['birth_place'],
                        'birth_date' => $row['birth_date'],
                        'gender' => $row['gender'],
                        'father_name' => $row['father_name'],
                        'mother_name' => $row['mother_name'],
                        'father_whatsapp' => $row['father_whatsapp'],
                        'mother_whatsapp' => $row['mother_whatsapp'],
                        'province' => $row['province'],
                        'city' => $row['city'],
                        'district' => $row['district'],
                        'village' => $row['village'],
                        'address' => $row['address'],
                        'school_class_id' => $class->id,
                        'academic_year_id' => $activeYear->id,
                        'entry_date' => $row['entry_date'] ?? now()->toDateString(),
                        'billing_start_date' => $row['billing_start_date'],
                        'exit_date' => $row['is_active'] ? null : $row['exit_date'],
                        'inactive_reason' => $row['is_active'] ? null : $row['inactive_reason'],
                        'is_active' => $row['is_active'],
                    ]);

                    $row['status'] = 'Valid';
                    $row['message'] = $persist ? 'Berhasil diimpor.' : 'Siap diimpor.';
                    $result['valid']++;
                    $result['imported'] += $persist ? 1 : 0;
                    $result['rows'][] = $row;
                } catch (Throwable $exception) {
                    $this->fail($result, $row, 'Data siswa tidak dapat diproses: '.$exception->getMessage());
                }
            }

            if ($persist) {
                DB::commit();
            }

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
        $headerIndex = collect($rows)->search(
            fn (array $row) => in_array('nis', array_map($this->normalizeHeader(...), $row), true)
        );
        if ($headerIndex === false) {
            throw ValidationException::withMessages(['file' => 'Header NIS tidak ditemukan pada file Excel.']);
        }

        $headers = array_map($this->normalizeHeader(...), $rows[$headerIndex]);
        $required = ['nis', 'nama', 'jenis_kelamin', 'unit_pendidikan', 'kelas'];
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
        $source = array_combine($headers, array_slice(array_pad($values, count($headers), null), 0, count($headers)));
        $nis = trim((string) ($source['nis'] ?? ''));
        $nisn = $this->nullable($source['nisn'] ?? null);
        $name = trim((string) ($source['nama'] ?? ''));
        $unit = trim((string) ($source['unit_pendidikan'] ?? ''));
        $class = trim((string) ($source['kelas'] ?? ''));
        $genderValue = strtolower(trim((string) ($source['jenis_kelamin'] ?? '')));
        $gender = in_array($genderValue, ['l', 'laki-laki', 'laki laki', 'male'], true)
            ? 'L'
            : (in_array($genderValue, ['p', 'perempuan', 'female'], true) ? 'P' : null);
        $status = strtolower(trim((string) ($source['status'] ?? 'aktif')));
        $isActive = ! in_array($status, ['nonaktif', 'tidak aktif', 'keluar'], true);
        $entryDate = $this->normalizeDate($source['tanggal_masuk'] ?? null);
        $billingStartDate = $this->normalizeDate(
            $source['mulai_tagihan_khusus']
                ?? $source['tanggal_mulai_tagihan']
                ?? $source['mulai_tagihan']
                ?? null
        );
        $exitDate = $this->normalizeDate($source['tanggal_keluar'] ?? null);
        $inactiveReason = $this->nullable($source['alasan_nonaktif'] ?? null);

        $base = [
            'line' => $line,
            'nis' => $nis,
            'nisn' => $nisn,
            'name' => $name,
            'unit' => $unit,
            'class' => $class,
            'gender' => $gender,
            'birth_place' => $this->nullable($source['tempat_lahir'] ?? null),
            'birth_date' => $this->normalizeDate($source['tanggal_lahir'] ?? null),
            'father_name' => $this->nullable($source['nama_ayah'] ?? null),
            'mother_name' => $this->nullable($source['nama_ibu'] ?? null),
            'father_whatsapp' => $this->nullable($source['no_wa_ayah'] ?? null),
            'mother_whatsapp' => $this->nullable($source['no_wa_ibu'] ?? null),
            'province' => $this->nullable($source['provinsi'] ?? null),
            'city' => $this->nullable($source['kabupaten_kota'] ?? null),
            'district' => $this->nullable($source['kecamatan'] ?? null),
            'village' => $this->nullable($source['desa'] ?? null),
            'address' => $this->nullable($source['alamat'] ?? null),
            'entry_date' => $entryDate,
            'billing_start_date' => $billingStartDate,
            'exit_date' => $exitDate,
            'inactive_reason' => $inactiveReason,
            'is_active' => $isActive,
        ];

        $error = match (true) {
            $nis === '' => 'NIS kosong.',
            $name === '' => 'Nama siswa kosong.',
            $unit === '' => 'Unit pendidikan kosong.',
            $class === '' => 'Kelas kosong.',
            $gender === null => 'Jenis kelamin tidak valid.',
            ! $isActive && ($exitDate === null || $inactiveReason === null) => 'Siswa nonaktif wajib memiliki Tanggal Keluar dan Alasan Nonaktif.',
            default => null,
        };

        return $error ? $base + ['status' => 'Gagal', 'message' => $error, 'error' => true] : $base;
    }

    private function fail(array &$result, array $row, string $message): void
    {
        $row['status'] = 'Gagal';
        $row['message'] = $message;
        $result['failures'][] = $row;
        $result['rows'][] = $row;
    }

    private function matchesUnit(EducationUnit $unit, string $value): bool
    {
        $normalized = $this->normalizeLookup($value);

        return $this->normalizeLookup($unit->code) === $normalized
            || $this->normalizeLookup($unit->name) === $normalized;
    }

    private function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
        $header = strtolower(trim($header));
        $header = str_replace(['.', '/', ' '], ['', '_', '_'], $header);

        return preg_replace('/_+/', '_', $header);
    }

    private function normalizeDate(mixed $date): ?string
    {
        $date = trim((string) $date);
        if ($date === '') {
            return null;
        }

        if (is_numeric($date)) {
            return (new \DateTimeImmutable('1899-12-30'))->modify('+'.(int) $date.' days')->format('Y-m-d');
        }

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y'] as $format) {
            $parsed = \DateTimeImmutable::createFromFormat('!'.$format, $date);
            if ($parsed && $parsed->format($format) === $date) {
                return $parsed->format('Y-m-d');
            }
        }

        return null;
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeLookup(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(trim($value)));
    }
}
