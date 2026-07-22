<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\EducationUnit;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardian_uses_single_bills_menu_and_only_sees_linked_student(): void
    {
        $this->seedRole('orang_tua');
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'MTs Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '7A', 'level' => 7, 'is_active' => true]);
        $linkedStudent = $this->student($class, $year, '260001', 'Siswa Terhubung');
        $otherStudent = $this->student($class, $year, '260002', 'Siswa Unit Sama');
        $this->bill($linkedStudent, $year, 'SPP Juli 2026', 7);
        $this->bill($linkedStudent, $year, 'SPP Agustus 2026', 8);
        $this->bill($otherStudent, $year, 'SPP Siswa Lain');

        $guardian = User::factory()->create(['role' => 'orang_tua']);
        $guardian->guardianStudents()->attach($linkedStudent->id);

        $this->actingAs($guardian)
            ->get('/keuangan/tagihan')
            ->assertOk()
            ->assertSee('Tagihan')
            ->assertSee('SISWA TERHUBUNG')
            ->assertSee('SPP MTs')
            ->assertSee('1 Tagihan')
            ->assertSee('Bayar sampai')
            ->assertSee('Transfer Bank')
            ->assertSee('Pembayaran tunai dilayani langsung di kantor.')
            ->assertSee('Kirim Bukti')
            ->assertSee('Riwayat Pembayaran')
            ->assertSee('Belum ada riwayat pembayaran.')
            ->assertDontSee('Tagihan Anak')
            ->assertDontSee('Perbarui Tagihan')
            ->assertDontSee('Belum ada riwayat transfer.')
            ->assertDontSee('guardian-history-table')
            ->assertDontSee('Siswa Unit Sama')
            ->assertDontSee('SPP Siswa Lain');

        $this->actingAs($guardian)
            ->get('/keuangan/tagihan/siswa/'.$otherStudent->id)
            ->assertForbidden();

        $this->actingAs($guardian)
            ->get('/wali-santri/tagihan')
            ->assertRedirect('/keuangan/tagihan');
    }

    public function test_unit_treasurer_only_sees_assigned_unit_bills(): void
    {
        $this->seedRole('bendahara');
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $assignedUnit = EducationUnit::create(['code' => 'RA', 'name' => 'RA Mambaul Hikmah', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'MA', 'name' => 'MA Mambaul Hikmah', 'is_active' => true]);
        $assignedClass = SchoolClass::create(['education_unit_id' => $assignedUnit->id, 'name' => 'A1', 'level' => 1, 'is_active' => true]);
        $otherClass = SchoolClass::create(['education_unit_id' => $otherUnit->id, 'name' => '10A', 'level' => 10, 'is_active' => true]);
        $assignedStudent = $this->student($assignedClass, $year, '270001', 'Siswa Unit Bendahara');
        $otherStudent = $this->student($otherClass, $year, '270002', 'Siswa Unit Lain');
        $this->bill($assignedStudent, $year, 'Tagihan Unit Bendahara');
        $this->bill($otherStudent, $year, 'Tagihan Unit Lain');

        $treasurer = User::factory()->create(['role' => 'bendahara']);
        $treasurer->educationUnits()->attach($assignedUnit->id);

        $this->actingAs($treasurer)
            ->get('/keuangan/tagihan')
            ->assertOk()
            ->assertSee('Tagihan Siswa')
            ->assertSee('Siswa Unit Bendahara')
            ->assertSee('Tagihan per Unit')
            ->assertSee('<strong>RA</strong>', false)
            ->assertDontSee('Tagihan Anak')
            ->assertDontSee('Siswa Unit Lain')
            ->assertDontSee('MA Mambaul Hikmah');
    }

    private function seedRole(string $key): void
    {
        Role::updateOrCreate(
            ['key' => $key],
            [
                'name' => Role::DEFAULTS[$key],
                'permissions' => Role::defaultPermissionsFor($key),
                'is_active' => true,
            ],
        );
    }

    private function student(SchoolClass $class, AcademicYear $year, string $nis, string $name): Student
    {
        return Student::create([
            'nis' => $nis,
            'name' => $name,
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'entry_date' => '2025-07-01',
            'is_active' => true,
        ]);
    }

    private function bill(Student $student, AcademicYear $year, string $title, int $month = 7): Bill
    {
        return Bill::create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'source_type' => 'spp',
            'year' => 2026,
            'month' => $month,
            'generation_key' => 'test-'.$student->nis.'-'.$month,
            'title' => $title,
            'issue_date' => sprintf('2026-%02d-01', $month),
            'due_date' => sprintf('2026-%02d-10', $month),
            'original_amount' => 100000,
            'total_amount' => 100000,
            'remaining_amount' => 100000,
            'status' => 'Belum Dibayar',
        ]);
    }
}
