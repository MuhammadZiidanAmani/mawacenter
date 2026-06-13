<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\EducationUnit;
use App\Models\Student;
use App\Support\StudentXlsx;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class StudentImportService
{
    public function import(UploadedFile $file, AcademicYear $activeYear): array
    {
        $rows = StudentXlsx::read($file->getRealPath());
        $rawHeaders = array_shift($rows) ?: [];
        $headers = array_map($this->normalizeHeader(...), $rawHeaders);
        $required = ['nis', 'nama', 'jenis_kelamin', 'unit_pendidikan', 'kelas'];

        if (array_diff($required, $headers)) {
            return [
                'imported' => 0,
                'created_classes' => 0,
                'failures' => ['Kolom NIS, Nama, Jenis Kelamin, Unit Pendidikan, dan Kelas wajib tersedia.'],
            ];
        }

        $result = ['imported' => 0, 'created_classes' => 0, 'failures' => []];

        DB::transaction(function () use ($rows, $headers, $activeYear, &$result) {
            $units = EducationUnit::with('schoolClasses')->get();

            foreach ($rows as $rowIndex => $values) {
                $row = array_combine($headers, array_slice(array_pad($values, count($headers), null), 0, count($headers)));
                $line = $rowIndex + 2;
                $nis = trim((string) ($row['nis'] ?? ''));

                if ($nis === '') {
                    $result['failures'][] = "Baris {$line}: NIS kosong.";
                    continue;
                }

                if (Student::where('nis', $nis)->exists()) {
                    $result['failures'][] = "Baris {$line}: NIS \"{$nis}\" sudah digunakan.";
                    continue;
                }

                $unitName = trim((string) ($row['unit_pendidikan'] ?? ''));
                $unit = $units->first(fn ($item) => $this->normalizeLookup($item->code) === $this->normalizeLookup($unitName)
                    || $this->normalizeLookup($item->name) === $this->normalizeLookup($unitName));
                if (! $unit) {
                    $result['failures'][] = "Baris {$line}: Unit Pendidikan \"{$unitName}\" tidak ditemukan.";
                    continue;
                }

                $className = trim((string) ($row['kelas'] ?? ''));
                if ($className === '') {
                    $result['failures'][] = "Baris {$line}: Kelas kosong.";
                    continue;
                }

                $class = $unit->schoolClasses->first(fn ($item) => $this->normalizeLookup($item->name) === $this->normalizeLookup($className));
                if (! $class) {
                    $class = $unit->schoolClasses()->create(['name' => $className, 'level' => 'Kelas '.$className]);
                    $unit->schoolClasses->push($class);
                    $result['created_classes']++;
                }

                $gender = strtolower(trim((string) ($row['jenis_kelamin'] ?? '')));
                $status = strtolower(trim((string) ($row['status'] ?? 'aktif')));
                $isActive = ! in_array($status, ['nonaktif', 'tidak aktif', 'keluar']);
                $exitDate = $this->normalizeDate($row['tanggal_keluar'] ?? null);
                $inactiveReason = trim((string) ($row['alasan_nonaktif'] ?? ''));
                if (! $isActive && (! $exitDate || $inactiveReason === '')) {
                    $result['failures'][] = "Baris {$line}: siswa nonaktif wajib memiliki Tanggal Keluar dan Alasan Nonaktif.";
                    continue;
                }

                Student::create([
                    'nis' => $nis,
                    'nisn' => $row['nisn'] ?: null,
                    'name' => $row['nama'],
                    'birth_place' => $row['tempat_lahir'] ?: null,
                    'birth_date' => $this->normalizeDate($row['tanggal_lahir'] ?? null),
                    'gender' => in_array($gender, ['l', 'laki-laki', 'laki laki', 'male']) ? 'L' : 'P',
                    'father_name' => $row['nama_ayah'] ?: null,
                    'mother_name' => $row['nama_ibu'] ?: null,
                    'father_whatsapp' => $row['no_wa_ayah'] ?: null,
                    'mother_whatsapp' => $row['no_wa_ibu'] ?: null,
                    'province' => $row['provinsi'] ?: null,
                    'city' => $row['kabupaten_kota'] ?: null,
                    'district' => $row['kecamatan'] ?: null,
                    'village' => $row['desa'] ?: null,
                    'address' => $row['alamat'] ?: null,
                    'school_class_id' => $class->id,
                    'academic_year_id' => $activeYear->id,
                    'entry_date' => $this->normalizeDate($row['tanggal_masuk'] ?? null) ?? now()->toDateString(),
                    'exit_date' => $isActive ? null : $exitDate,
                    'inactive_reason' => $isActive ? null : $inactiveReason,
                    'is_active' => $isActive,
                ]);
                $result['imported']++;
            }
        });

        return $result;
    }

    private function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
        $header = strtolower(trim($header));
        $header = str_replace(['.', '/', ' '], ['', '_', '_'], $header);

        return preg_replace('/_+/', '_', $header);
    }

    private function normalizeDate(?string $date): ?string
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

    private function normalizeLookup(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(trim($value)));
    }
}
