<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\EducationUnit;
use App\Models\FeeType;
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
            ->assertSee('Unit Pendidikan')
            ->assertSee('Jumlah Tagihan')
            ->assertSee('Total Keseluruhan')
            ->assertSee('RA Mambaul Hikmah')
            ->assertDontSee('Tagihan per Unit')
            ->assertDontSee('Tagihan Anak')
            ->assertDontSee('Siswa Unit Lain')
            ->assertDontSee('MA Mambaul Hikmah');

        $this->actingAs($treasurer)
            ->get('/keuangan/tagihan/siswa/'.$assignedStudent->id)
            ->assertOk()
            ->assertSee('Penertiban Administrasi Keuangan')
            ->assertSee('Siswa Unit Bendahara')
            ->assertSee('Kembali')
            ->assertSee('Bayar')
            ->assertSee('Unduh')
            ->assertSee('Cetak')
            ->assertDontSee('1 / 1')
            ->assertDontSee('notice-toolbar-zoom');

        $this->actingAs($treasurer)
            ->get('/keuangan/tagihan/siswa/'.$otherStudent->id)
            ->assertForbidden();
    }

    public function test_admin_bill_detail_has_payment_action_and_notice_content(): void
    {
        $this->seedRole('admin');
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '4A', 'level' => 4, 'is_active' => true]);
        $student = $this->student($class, $year, '290001', 'Siswa Detail Tagihan');
        $this->bill($student, $year, 'SPP Juli 2026', 7);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/keuangan/tagihan/siswa/'.$student->id.'?year=2026&until_month=7')
            ->assertOk()
            ->assertSee('Penertiban Administrasi Keuangan')
            ->assertSee('SISWA DETAIL TAGIHAN')
            ->assertSee('MI Mambaul Hikmah')
            ->assertSee('4A')
            ->assertSee('SPP JULI')
            ->assertSee('Total Keseluruhan')
            ->assertSee('Terbilang:')
            ->assertSee('href="http://localhost/keuangan/pembayaran?student_id='.$student->id.'&amp;search=290001"', false)
            ->assertSee('Unduh')
            ->assertSee('Cetak');
    }

    public function test_bill_detail_only_contains_unfinished_non_laundry_bills(): void
    {
        $this->seedRole('admin');
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'PONPES Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'Asrama', 'level' => 1, 'is_active' => true]);
        $student = $this->student($class, $year, '290002', 'Siswa Detail Bersih');
        $laundryFee = FeeType::create([
            'education_unit_id' => $unit->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'laundry',
            'code' => 'LAUNDRY-DETAIL',
            'name' => 'Laundry Detail',
            'amount' => 50000,
            'period' => 'Bulanan',
            'creates_bill' => true,
            'is_active' => true,
        ]);

        $this->bill($student, $year, 'SPP Juli 2026', 7);
        $this->bill($student, $year, 'SPP Agustus 2026', 8, [
            'paid_amount' => 100000,
            'remaining_amount' => 0,
            'status' => 'Lunas',
        ]);
        $this->bill($student, $year, 'SPP September 2026', 9, [
            'status' => 'Dibatalkan',
            'cancel_reason' => 'Test',
        ]);
        $this->bill($student, $year, 'Laundry Juli 2026', 7, [
            'source_type' => 'fee_type',
            'fee_type_id' => $laundryFee->id,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/keuangan/tagihan/siswa/'.$student->id.'?year=2026&until_month=9')
            ->assertOk()
            ->assertSee('SPP JULI')
            ->assertDontSee('SPP AGUSTUS')
            ->assertDontSee('SPP SEPTEMBER')
            ->assertDontSee('Laundry Juli 2026')
            ->assertDontSee('Laundry Detail');
    }

    public function test_admin_bill_pagination_uses_application_labels(): void
    {
        $this->seedRole('admin');
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PAUD', 'name' => 'PAUD Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'KB', 'level' => 1, 'is_active' => true]);

        foreach (range(1, 11) as $index) {
            $student = $this->student($class, $year, sprintf('280%03d', $index), 'Siswa Tagihan '.$index);
            $this->bill($student, $year, 'Tagihan Siswa '.$index);
        }

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/keuangan/tagihan?per_page=10')
            ->assertOk()
            ->assertSee('Menampilkan 1-10 dari 11 siswa')
            ->assertSee('Berikutnya')
            ->assertDontSee('Showing')
            ->assertDontSee('Halaman');
    }

    public function test_admin_bill_menu_ignores_status_query_and_only_shows_outstanding_bills(): void
    {
        $this->seedRole('admin');
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '5A', 'level' => 5, 'is_active' => true]);
        $outstandingStudent = $this->student($class, $year, '291001', 'Siswa Masih Tagihan');
        $paidStudent = $this->student($class, $year, '291002', 'Siswa Sudah Lunas');
        $canceledStudent = $this->student($class, $year, '291003', 'Siswa Batal Tagihan');

        $this->bill($outstandingStudent, $year, 'Tagihan Aktif');
        $this->bill($paidStudent, $year, 'Tagihan Lunas', 8, [
            'paid_amount' => 100000,
            'remaining_amount' => 0,
            'status' => 'Lunas',
        ]);
        $this->bill($canceledStudent, $year, 'Tagihan Dibatalkan', 9, [
            'status' => 'Dibatalkan',
            'cancel_reason' => 'Test',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['paid', 'all', 'partial', 'overdue'] as $status) {
            $this->actingAs($admin)
                ->get('/keuangan/tagihan?status='.$status)
                ->assertOk()
                ->assertSee('Siswa Masih Tagihan')
                ->assertDontSee('Siswa Sudah Lunas')
                ->assertDontSee('Siswa Batal Tagihan');
        }
    }

    public function test_admin_can_search_bills_by_student_nisn(): void
    {
        $this->seedRole('admin');
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'RA', 'name' => 'RA Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'B1', 'level' => 1, 'is_active' => true]);
        $matchedStudent = $this->student($class, $year, '292001', 'Siswa Cari NISN', '1234567890');
        $otherStudent = $this->student($class, $year, '292002', 'Siswa Tidak Dicari', '9999999999');
        $this->bill($matchedStudent, $year, 'Tagihan NISN');
        $this->bill($otherStudent, $year, 'Tagihan Lain');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/keuangan/tagihan?student_search=1234567890')
            ->assertOk()
            ->assertSee('Siswa Cari NISN')
            ->assertDontSee('Siswa Tidak Dicari');
    }

    public function test_unit_summary_follows_active_filters_and_totals_all_filtered_rows(): void
    {
        $this->seedRole('admin');
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $paud = EducationUnit::create(['code' => 'PAUD', 'name' => 'PAUD Mambaul Hikmah', 'is_active' => true]);
        $ma = EducationUnit::create(['code' => 'MA', 'name' => 'MA Mambaul Hikmah', 'is_active' => true]);
        $paudClass = SchoolClass::create(['education_unit_id' => $paud->id, 'name' => 'KB', 'level' => 1, 'is_active' => true]);
        $maClass = SchoolClass::create(['education_unit_id' => $ma->id, 'name' => '10A', 'level' => 10, 'is_active' => true]);
        $paudStudent = $this->student($paudClass, $year, '293001', 'Siswa PAUD Ringkasan');
        $maStudent = $this->student($maClass, $year, '293002', 'Siswa MA Ringkasan');
        $this->bill($paudStudent, $year, 'Tagihan PAUD', 7, ['remaining_amount' => 75000, 'total_amount' => 75000, 'original_amount' => 75000]);
        $this->bill($maStudent, $year, 'Tagihan MA', 7, ['remaining_amount' => 125000, 'total_amount' => 125000, 'original_amount' => 125000]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/keuangan/tagihan?unit_id='.$paud->id)
            ->assertOk()
            ->assertSee('PAUD Mambaul Hikmah')
            ->assertSee('75.000')
            ->assertDontSee('MA Mambaul Hikmah')
            ->assertDontSee('125.000');
    }

    public function test_laundry_bills_are_not_shown_for_admin_or_guardian(): void
    {
        $this->seedRole('admin');
        $this->seedRole('orang_tua');
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'PONPES Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'Asrama', 'level' => 1, 'is_active' => true]);
        $regularStudent = $this->student($class, $year, '294001', 'Siswa Tagihan Biasa');
        $laundryStudent = $this->student($class, $year, '294002', 'Siswa Laundry Saja');
        $laundryFee = FeeType::create([
            'education_unit_id' => $unit->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'laundry',
            'code' => 'LAUNDRY',
            'name' => 'Laundry',
            'amount' => 50000,
            'period' => 'Bulanan',
            'creates_bill' => true,
            'is_active' => true,
        ]);

        $this->bill($regularStudent, $year, 'Tagihan Biasa');
        $this->bill($laundryStudent, $year, 'Laundry Juli 2026', 7, [
            'source_type' => 'fee_type',
            'fee_type_id' => $laundryFee->id,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $guardian = User::factory()->create(['role' => 'orang_tua']);
        $guardian->guardianStudents()->attach($laundryStudent->id);

        $this->actingAs($admin)
            ->get('/keuangan/tagihan')
            ->assertOk()
            ->assertSee('Siswa Tagihan Biasa')
            ->assertDontSee('Siswa Laundry Saja')
            ->assertDontSee('Laundry Juli 2026');

        $this->actingAs($guardian)
            ->get('/keuangan/tagihan')
            ->assertOk()
            ->assertSee('Tidak ada tagihan aktif untuk siswa ini.')
            ->assertDontSee('Laundry Juli 2026');
    }

    public function test_opening_bill_menu_does_not_auto_create_missing_bills(): void
    {
        $this->seedRole('admin');
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'RA', 'name' => 'RA Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'A2', 'level' => 1, 'is_active' => true]);
        $this->student($class, $year, '295001', 'Siswa Belum Ada Bill');
        FeeType::create([
            'education_unit_id' => $unit->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'daftar-ulang',
            'code' => 'DU-TEST',
            'name' => 'Daftar Ulang Test',
            'amount' => 150000,
            'period' => 'Tahunan',
            'creates_bill' => true,
            'is_active' => true,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertSame(0, Bill::count());

        $this->actingAs($admin)
            ->get('/keuangan/tagihan')
            ->assertOk();

        $this->assertSame(0, Bill::count());
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

    private function student(SchoolClass $class, AcademicYear $year, string $nis, string $name, ?string $nisn = null): Student
    {
        return Student::create([
            'nis' => $nis,
            'nisn' => $nisn,
            'name' => $name,
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'entry_date' => '2025-07-01',
            'is_active' => true,
        ]);
    }

    private function bill(Student $student, AcademicYear $year, string $title, int $month = 7, array $overrides = []): Bill
    {
        return Bill::create(array_merge([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'source_type' => 'spp',
            'year' => 2026,
            'month' => $month,
            'generation_key' => 'test-'.$student->nis.'-'.$month.'-'.substr(md5($title), 0, 8),
            'title' => $title,
            'issue_date' => sprintf('2026-%02d-01', $month),
            'due_date' => sprintf('2026-%02d-10', $month),
            'original_amount' => 100000,
            'total_amount' => 100000,
            'remaining_amount' => 100000,
            'status' => 'Belum Dibayar',
        ], $overrides));
    }
}
