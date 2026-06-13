<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Models\EducationUnit;
use App\Models\FeeDiscount;
use App\Models\FeeType;
use App\Models\OtherPayment;
use App\Models\SchoolClass;
use App\Models\SppSetting;
use App\Models\SppPayment;
use App\Models\SppPaymentItem;
use App\Models\Student;
use App\Services\ChargeCalculator;
use App\Support\StudentXlsx;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_data_page_can_be_opened(): void
    {
        $this->get('/master-data')->assertOk()->assertSee('Data Siswa');
    }

    public function test_all_master_create_forms_use_dedicated_pages(): void
    {
        foreach ([
            'academic-years' => 'Tambah Tahun Pelajaran',
            'education-units' => 'Tambah Unit Pendidikan',
            'classes' => 'Tambah Kelas',
            'students' => 'Tambah Siswa',
            'fee-types' => 'Tambah Jenis Pembayaran',
            'spp-settings' => 'Tambah Set SPP',
            'fee-discounts' => 'Tambah Keringanan',
        ] as $tab => $heading) {
            $this->get('/master-data?tab='.$tab)
                ->assertOk()
                ->assertSee('/master-data/create?tab='.$tab, false);

            $this->get('/master-data/create?tab='.$tab)
                ->assertOk()
                ->assertSee($heading)
                ->assertSee('Kembali ke Daftar')
                ->assertDontSee('data-modal-open', false);
        }

        $this->get('/master-data/create?tab=students')
            ->assertOk()
            ->assertSee('data-copy-father-whatsapp', false)
            ->assertSee('data-student-region="province"', false)
            ->assertSee('data-student-region="city"', false)
            ->assertSee('data-student-region="district"', false)
            ->assertSee('data-student-region="village"', false);

        $this->get('/master-data/create?tab=fee-types')
            ->assertOk()
            ->assertSee('data-currency-input', false);

        $this->get('/master-data/create?tab=spp-settings')
            ->assertOk()
            ->assertSee('data-currency-input', false);
    }

    public function test_all_master_data_can_be_created(): void
    {
        $this->post('/master-data/academic-years', ['name' => '2025/2026', 'is_active' => 1])->assertRedirect();
        $this->post('/master-data/education-units', ['code' => 'SMK', 'name' => 'Sekolah Menengah Kejuruan', 'is_active' => 1])->assertRedirect();
        $this->post('/master-data/classes', ['education_unit_id' => EducationUnit::where('code', 'SMK')->first()->id, 'name' => 'VII A', 'is_active' => 1])->assertRedirect();

        $year = AcademicYear::first();
        $class = SchoolClass::first();
        $this->post('/master-data/students', [
            'nis' => '1001', 'nisn' => '2001', 'name' => 'Alya Maharani', 'gender' => 'P',
            'education_unit_id' => $class->education_unit_id, 'school_class_id' => $class->id, 'academic_year_id' => $year->id,
            'entry_date' => '2026-06-11', 'is_active' => 1,
        ])->assertRedirect();
        $this->post('/master-data/fee-types', [
            'name' => 'SPP Bulanan', 'education_unit_id' => $class->education_unit_id,
            'school_class_id' => $class->id, 'amount' => 350000, 'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseCount('students', 1);
        $this->assertTrue(Student::first()->is_active);
        $this->assertTrue(FeeType::first()->is_active);
    }

    public function test_fee_type_uses_matching_education_unit_and_class(): void
    {
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'MA', 'name' => 'Madrasah Aliyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);

        $this->post('/master-data/fee-types', [
            'name' => 'SPP Bulanan', 'education_unit_id' => $otherUnit->id,
            'school_class_id' => $class->id, 'amount' => 350000, 'is_active' => 1,
        ])->assertSessionHasErrors('school_class_id');

        $this->post('/master-data/fee-types', [
            'name' => 'SPP Bulanan', 'education_unit_id' => $unit->id,
            'school_class_id' => $class->id, 'amount' => 350000, 'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('fee_types', [
            'name' => 'SPP Bulanan',
            'education_unit_id' => $unit->id,
            'school_class_id' => $class->id,
            'amount' => 350000,
        ]);
    }

    public function test_fee_type_can_apply_to_all_classes_in_education_unit(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PP', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'MA', 'name' => 'Madrasah Aliyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'Salaf', 'level' => 'Kelas Salaf']);
        $otherClass = SchoolClass::create(['education_unit_id' => $otherUnit->id, 'name' => 'X A', 'level' => 'Kelas X A']);
        $student = Student::create(['nis' => '9001', 'name' => 'Santri', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $year->id]);
        $otherStudent = Student::create(['nis' => '9002', 'name' => 'Siswa MA', 'gender' => 'P', 'school_class_id' => $otherClass->id, 'academic_year_id' => $year->id]);

        $this->post('/master-data/fee-types', [
            'name' => 'Pendaftaran Pondok', 'education_unit_id' => $unit->id,
            'school_class_id' => 'all', 'amount' => 2000000, 'is_active' => 1,
        ])->assertRedirect();

        $feeType = FeeType::first();
        $this->assertNull($feeType->school_class_id);
        $this->assertSame(2000000, app(ChargeCalculator::class)->baseAmount($student, 'fee_type', $feeType));
        $this->assertSame(0, app(ChargeCalculator::class)->baseAmount($otherStudent, 'fee_type', $feeType));
        $this->get('/master-data?tab=fee-types')->assertOk()->assertSee('Semua Kelas');
    }

    public function test_spp_setting_is_unique_per_education_unit(): void
    {
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);

        $this->post('/master-data/spp-settings', [
            'education_unit_id' => $unit->id, 'amount' => 250000, 'is_active' => 1,
        ])->assertRedirect();

        $this->post('/master-data/spp-settings', [
            'education_unit_id' => $unit->id, 'amount' => 300000, 'is_active' => 1,
        ])->assertSessionHasErrors('education_unit_id');

        $this->assertDatabaseCount('spp_settings', 1);
        $this->assertTrue(SppSetting::first()->is_active);
    }

    public function test_spp_discount_reduces_student_charge(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create(['nis' => '1001', 'name' => 'Ahmad', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $year->id]);
        SppSetting::create(['education_unit_id' => $unit->id, 'amount' => 600000, 'is_active' => true]);

        $this->post('/master-data/fee-discounts', [
            'student_id' => $student->id, 'source_type' => 'spp', 'discount_type' => 'amount',
            'discount_value' => 300000, 'start_date' => now()->subDay()->toDateString(), 'is_active' => 1,
        ])->assertRedirect();

        $charge = app(ChargeCalculator::class)->calculate($student, 'spp');
        $this->assertSame(600000, $charge['original_amount']);
        $this->assertSame(300000, $charge['discount_amount']);
        $this->assertSame(300000, $charge['final_amount']);
        $this->get('/master-data?tab=fee-discounts')
            ->assertOk()
            ->assertSee('Keringanan Biaya')
            ->assertSee('Set Biaya')
            ->assertSee('Yang Dibayarkan')
            ->assertDontSee('Periode')
            ->assertSee('Rp 300.000');
    }

    public function test_spp_discount_applies_when_period_starts_mid_month(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PP', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '9A', 'level' => 'Kelas 9']);
        $student = Student::create(['nis' => '1002', 'name' => 'Zidan', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        SppSetting::create(['education_unit_id' => $unit->id, 'amount' => 600000, 'is_active' => true]);
        FeeDiscount::create([
            'student_id' => $student->id, 'source_type' => 'spp', 'discount_type' => 'amount',
            'discount_value' => 350000, 'start_date' => '2026-06-12', 'is_active' => true,
        ]);

        $charge = app(ChargeCalculator::class)->calculateSppMonth($student, 2026, 6);

        $this->assertSame(600000, $charge['original_amount']);
        $this->assertSame(350000, $charge['discount_amount']);
        $this->assertSame(250000, $charge['final_amount']);
    }

    public function test_other_payment_discount_reduces_only_selected_fee_type(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MA', 'name' => 'Madrasah Aliyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'X A', 'level' => 'Kelas X']);
        $student = Student::create(['nis' => '2001', 'name' => 'Bella', 'gender' => 'P', 'school_class_id' => $class->id, 'academic_year_id' => $year->id]);
        $feeType = FeeType::create([
            'education_unit_id' => $unit->id, 'school_class_id' => $class->id, 'code' => 'GEDUNG',
            'name' => 'Uang Gedung', 'amount' => 2000000, 'period' => 'Sekali Bayar', 'is_active' => true,
        ]);

        $this->post('/master-data/fee-discounts', [
            'student_id' => $student->id, 'source_type' => 'fee_type', 'fee_type_id' => $feeType->id,
            'discount_type' => 'amount', 'discount_value' => 500000,
            'start_date' => now()->subDay()->toDateString(), 'is_active' => 1,
        ])->assertRedirect();

        $charge = app(ChargeCalculator::class)->calculate($student, 'fee_type', $feeType);
        $this->assertSame(2000000, $charge['original_amount']);
        $this->assertSame(500000, $charge['discount_amount']);
        $this->assertSame(1500000, $charge['final_amount']);
    }

    public function test_discount_cannot_exceed_original_amount_or_overlap(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'Madrasah Ibtidaiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '1 A', 'level' => 'Kelas 1']);
        $student = Student::create(['nis' => '3001', 'name' => 'Candra', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $year->id]);
        SppSetting::create(['education_unit_id' => $unit->id, 'amount' => 400000, 'is_active' => true]);

        $this->post('/master-data/fee-discounts', [
            'student_id' => $student->id, 'source_type' => 'spp', 'discount_type' => 'amount',
            'discount_value' => 500000, 'start_date' => '2026-01-01', 'is_active' => 1,
        ])->assertSessionHasErrors('discount_value');

        FeeDiscount::create([
            'student_id' => $student->id, 'source_type' => 'spp', 'discount_type' => 'amount',
            'discount_value' => 100000, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);
        $this->post('/master-data/fee-discounts', [
            'student_id' => $student->id, 'source_type' => 'spp', 'discount_type' => 'percentage',
            'discount_value' => 50, 'start_date' => '2026-06-01', 'is_active' => 1,
        ])->assertSessionHasErrors('start_date');
    }

    public function test_spp_payment_supports_installment_and_later_settlement(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create(['nis' => '4001', 'name' => 'Dina', 'gender' => 'P', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        Student::create(['nis' => '4002', 'name' => 'Siswa Nonaktif', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => false]);
        SppSetting::create(['education_unit_id' => $unit->id, 'amount' => 600000, 'is_active' => true]);
        FeeDiscount::create([
            'student_id' => $student->id, 'source_type' => 'spp', 'discount_type' => 'amount',
            'discount_value' => 300000, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        $this->get('/keuangan/pembayaran/spp')->assertOk()->assertSee('Daftar Pembayaran SPP')->assertSee('/keuangan/pembayaran/spp/create');
        $this->get('/keuangan/pembayaran/spp?search=Dina&per_page=25')
            ->assertOk()->assertSee('Data Pembayaran SPP')->assertSee('Search:');
        $this->get('/keuangan/pembayaran/spp/create')->assertOk()->assertSee('Tambah Pembayaran SPP')->assertSee('Dina')->assertDontSee('Siswa Nonaktif');
        $this->getJson('/keuangan/pembayaran/spp/months?student_id='.$student->id.'&year=2026')
            ->assertOk()->assertJson(['first_payable_month' => 1]);
        $this->getJson('/keuangan/pembayaran/spp/quote?student_id='.$student->id.'&year=2026&months[]=6')
            ->assertUnprocessable();
        $this->getJson('/keuangan/pembayaran/spp/quote?student_id='.$student->id.'&year=2026&months[]=1&months[]=2')
            ->assertOk()->assertJson(['original_amount' => 1200000, 'discount_amount' => 600000, 'total_amount' => 600000]);

        $payload = [
            'transaction_date' => '2026-06-12', 'transaction_time' => '08:30', 'student_id' => $student->id,
            'months' => [1, 2], 'year' => 2026, 'payment_method' => 'Cash', 'status' => 'Diterima', 'paid_amount' => 200000,
        ];
        $this->post('/keuangan/pembayaran/spp', $payload)->assertRedirect();
        $this->get('/keuangan/pembayaran/spp?search=Dina&per_page=25')
            ->assertOk()->assertSee('Dina')->assertSee('Madrasah Tsanawiyah');
        $this->get('/keuangan/pembayaran/spp?search=Tidak-Ada')
            ->assertOk()->assertDontSee('Dina');

        $this->assertDatabaseHas('spp_payments', [
            'student_id' => $student->id, 'total_amount' => 600000, 'paid_amount' => 200000,
            'remaining_amount' => 400000, 'payment_status' => 'Belum Lunas',
        ]);
        $this->assertDatabaseHas('spp_payment_items', ['student_id' => $student->id, 'month' => 1, 'paid_amount' => 200000, 'remaining_amount' => 100000]);
        $this->getJson('/keuangan/pembayaran/spp/quote?student_id='.$student->id.'&year=2026&months[]=1&months[]=2')
            ->assertOk()->assertJson(['paid_amount' => 200000, 'remaining_amount' => 400000, 'payment_status' => 'Belum Lunas']);

        $this->post('/keuangan/pembayaran/spp', array_merge($payload, ['paid_amount' => 400000]))->assertRedirect();
        $this->assertDatabaseHas('spp_payments', ['student_id' => $student->id, 'paid_amount' => 400000, 'remaining_amount' => 0, 'payment_status' => 'Lunas']);
        $this->assertDatabaseCount('spp_payments', 2);
        $this->assertDatabaseCount('spp_payment_items', 3);

        $this->post('/keuangan/pembayaran/spp', array_merge($payload, ['paid_amount' => 1]))->assertSessionHasErrors('months');
        $this->getJson('/keuangan/pembayaran/spp/months?student_id='.$student->id.'&year=2026')
            ->assertOk()->assertJson(['first_payable_month' => 3]);
        $this->assertDatabaseCount('spp_payments', 2);
        $this->assertSame(600000, SppPayment::sum('paid_amount'));
        $this->assertSame(600000, SppPaymentItem::sum('paid_amount'));

        $payment = SppPayment::latest('id')->firstOrFail();
        $this->getJson('/keuangan/pembayaran/spp/'.$payment->id)
            ->assertOk()
            ->assertJsonPath('student.name', 'Dina')
            ->assertJsonPath('paid_amount', 400000);
        $receipt = $this->get('/keuangan/pembayaran/spp/'.$payment->id.'/receipt');
        $receipt->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $receipt->getContent());

        $this->put('/keuangan/pembayaran/spp/'.$payment->id, [
            'transaction_date' => '2026-06-13',
            'transaction_time' => '09:45',
            'payment_method' => 'Transfer',
            'status' => 'Pending',
        ])->assertRedirect('/keuangan/pembayaran/spp');
        $this->assertDatabaseHas('spp_payments', [
            'id' => $payment->id,
            'payment_method' => 'Transfer',
            'status' => 'Pending',
        ]);

        $this->delete('/keuangan/pembayaran/spp/'.$payment->id)->assertRedirect('/keuangan/pembayaran/spp');
        $this->assertDatabaseMissing('spp_payments', ['id' => $payment->id]);
        $this->assertDatabaseCount('spp_payments', 1);
        $this->assertDatabaseCount('spp_payment_items', 1);
        $this->getJson('/keuangan/pembayaran/spp/months?student_id='.$student->id.'&year=2026')
            ->assertOk()->assertJson(['first_payable_month' => 1]);
    }

    public function test_spp_payments_can_be_previewed_and_imported_from_monthly_report_xlsx(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Ponpes Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '10A', 'level' => 'Kelas 10']);
        Student::create([
            'nis' => '220001', 'name' => 'ABDILLAH SAEFI HAMMAM', 'gender' => 'L',
            'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true,
        ]);
        SppSetting::create(['education_unit_id' => $unit->id, 'amount' => 600000, 'is_active' => true]);

        $path = tempnam(sys_get_temp_dir(), 'spp-import-test-');
        StudentXlsx::write($path, [
            ['Data Laporan SPP'],
            ['No', 'NIS', 'Nama', 'Jenis Pendidikan', 'Kelas', 'Petugas', 'Cara bayar', 'Bulan', 'Tahun', 'Waktu', 'Nominal'],
            [1, '220001', 'ABDILLAH SAEFI HAMMAM', 'PONPES MAMBAUL HIKMAH', '10A', 'Ziidan Amani', 'cash', 'januari', 2026, '2026-01-06 10:08:00', 250000],
            [2, '220001', 'ABDILLAH SAEFI HAMMAM', 'PONPES MAMBAUL HIKMAH', '10A', 'Ziidan Amani', 'transfer', 'januari', 2026, '2026-01-07 10:08:00', 350000],
            [3, '999999', 'SISWA BELUM ADA', 'PONPES MAMBAUL HIKMAH', '10A', 'Ziidan Amani', 'cash', 'januari', 2026, '2026-01-08 10:08:00', 600000],
        ]);
        $workbook = file_get_contents($path);
        unlink($path);

        $preview = $this->post('/keuangan/pembayaran/spp/import/preview', [
            'file' => UploadedFile::fake()->createWithContent('laporan-spp.xlsx', $workbook),
        ]);

        $preview->assertOk()->assertSee('Preview Import Pembayaran')->assertSee('NIS 999999 tidak ditemukan.');
        $this->assertDatabaseCount('spp_payments', 0);
        $token = $preview->viewData('importToken');

        $this->post('/keuangan/pembayaran/spp/import', ['token' => $token])
            ->assertRedirect('/keuangan/pembayaran/spp')
            ->assertSessionHas('success');

        $this->assertDatabaseCount('spp_payments', 2);
        $this->assertDatabaseCount('spp_payment_items', 2);
        $this->assertSame(600000, SppPayment::sum('paid_amount'));
        $this->assertDatabaseHas('spp_payments', ['operator_name' => 'Ziidan Amani', 'payment_method' => 'Transfer']);
        $this->assertDatabaseHas('spp_payment_items', ['student_id' => Student::where('nis', '220001')->value('id'), 'month' => 1, 'paid_amount' => 350000, 'remaining_amount' => 0]);

        $duplicatePreview = $this->post('/keuangan/pembayaran/spp/import/preview', [
            'file' => UploadedFile::fake()->createWithContent('laporan-spp.xlsx', $workbook),
        ]);
        $duplicatePreview->assertOk();
        $this->assertSame(2, $duplicatePreview->viewData('importPreview')['duplicates']);
        $this->assertDatabaseCount('spp_payments', 2);
    }

    public function test_spp_receipt_opens_as_inline_pdf(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PP', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '12A', 'level' => 'Kelas 12']);
        $student = Student::create([
            'nis' => '230199',
            'name' => 'Muhammad Syarif Robiansyah',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $payment = SppPayment::create([
            'student_id' => $student->id,
            'transaction_at' => '2026-06-12 15:14:00',
            'payment_method' => 'Transfer',
            'status' => 'Diterima',
            'original_amount' => 600000,
            'discount_amount' => 0,
            'total_amount' => 600000,
            'paid_amount' => 600000,
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);
        $payment->items()->create([
            'student_id' => $student->id,
            'year' => 2026,
            'month' => 6,
            'original_amount' => 600000,
            'discount_amount' => 0,
            'total_amount' => 600000,
            'paid_amount' => 600000,
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);

        $receipt = $this->get('/keuangan/pembayaran/spp/'.$payment->id.'/receipt');

        $receipt->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('inline; filename="kwitansi_spp_230199_', $receipt->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF-', $receipt->getContent());
    }

    public function test_other_payment_uses_fee_type_and_automatic_discount(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create(['nis' => '5001', 'name' => 'Rina', 'gender' => 'P', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        $feeType = FeeType::create(['education_unit_id' => $unit->id, 'school_class_id' => $class->id, 'code' => 'DAFTAR-ULANG', 'name' => 'Daftar Ulang', 'amount' => 1000000, 'period' => 'once', 'is_active' => true]);
        FeeDiscount::create([
            'student_id' => $student->id, 'source_type' => 'fee_type', 'fee_type_id' => $feeType->id,
            'discount_type' => 'amount', 'discount_value' => 250000, 'start_date' => now()->subDay(), 'is_active' => true,
        ]);

        $this->get('/keuangan/pembayaran/lain-lain')
            ->assertOk()
            ->assertSee('Daftar Pembayaran Lain-lain')
            ->assertSee('Data Transaksi')
            ->assertSee('/keuangan/pembayaran/lain-lain/create');
        $this->get('/keuangan/pembayaran/lain-lain/create')
            ->assertOk()
            ->assertSee('Tambah Pembayaran Lain-lain')
            ->assertSee('Nominal Dibayar Sekarang')
            ->assertSee('Rina')
            ->assertSee('Daftar Ulang');
        $this->getJson('/keuangan/pembayaran/lain-lain/quote?student_id='.$student->id.'&fee_type_id='.$feeType->id)
            ->assertOk()->assertJson(['original_amount' => 1000000, 'discount_amount' => 250000, 'paid_amount' => 0, 'remaining_amount' => 750000]);
        $this->post('/keuangan/pembayaran/lain-lain', [
            'transaction_date' => '2026-06-12', 'transaction_time' => '10:15',
            'student_id' => $student->id, 'fee_type_id' => $feeType->id,
            'payment_method' => 'Transfer', 'status' => 'Diterima', 'paid_amount' => 500000,
        ])->assertRedirect('/keuangan/pembayaran/lain-lain');
        $this->assertDatabaseHas('other_payments', [
            'student_id' => $student->id, 'fee_type_id' => $feeType->id,
            'original_amount' => 1000000, 'discount_amount' => 250000, 'paid_amount' => 500000,
            'remaining_amount' => 250000, 'payment_status' => 'Belum Lunas',
        ]);
        $this->post('/keuangan/pembayaran/lain-lain', [
            'transaction_date' => '2026-06-12', 'transaction_time' => '11:15',
            'student_id' => $student->id, 'fee_type_id' => $feeType->id,
            'payment_method' => 'Cash', 'status' => 'Diterima', 'paid_amount' => 750001,
        ])->assertSessionHasErrors('paid_amount');
        $this->get('/keuangan/pembayaran/lain-lain?search=Rina&per_page=25')
            ->assertOk()
            ->assertSee('Rina')
            ->assertSee('Daftar Ulang')
            ->assertSee('500.000');
        $this->get('/keuangan/pembayaran/lain-lain?search=tidak-ada')
            ->assertOk()
            ->assertDontSee('Rina');
        $this->assertSame(500000, OtherPayment::sum('paid_amount'));
    }

    public function test_other_payments_can_be_mapped_previewed_and_imported_from_xlsx(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'PONPES MAMBAUL HIKMAH', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '7', 'level' => 'Kelas 7']);
        Student::create(['nis' => '260001', 'name' => 'ABDU ARIQIN HALIM', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        $feeType = FeeType::create(['education_unit_id' => $unit->id, 'code' => 'PONDOK-1447', 'name' => 'Pendaftaran Pondok 1447/1448 H', 'amount' => 6500000, 'period' => 'Sekali Bayar', 'is_active' => true]);

        $path = tempnam(sys_get_temp_dir(), 'other-import-test-');
        StudentXlsx::write($path, [
            ['No', 'NIS', 'Nama', 'Petugas', 'Kategori Pembayaran', 'Jenis Pendidikan', 'Kelas', 'Cara bayar', 'Nominal', 'Waktu'],
            [1, '260001', 'ABDU ARIQIN HALIM', 'Ziidan Amani', 'PENDAFTARAN PONDOK 1447/1448', 'PONPES MAMBAUL HIKMAH', '7', 'cash', 2000000, '2026-05-14 08:55:00'],
            [2, '260001', 'ABDU ARIQIN HALIM', 'Ziidan Amani', 'PENDAFTARAN PONDOK 1447/1448', 'PONPES MAMBAUL HIKMAH', '7', 'transfer', 4500000, '2026-05-15 08:55:00'],
            [3, '999999', 'SISWA BELUM ADA', 'Ziidan Amani', 'PENDAFTARAN PONDOK 1447/1448', 'PONPES MAMBAUL HIKMAH', '7', 'cash', 500000, '2026-05-16 08:55:00'],
        ]);
        $workbook = file_get_contents($path);
        unlink($path);

        $preview = $this->post('/keuangan/pembayaran/lain-lain/import/preview', [
            'file' => UploadedFile::fake()->createWithContent('transaksi.xlsx', $workbook),
        ]);
        $preview->assertOk()->assertSee('Pemetaan Kategori Pembayaran')->assertSee('NIS 999999 tidak ditemukan.');
        $this->assertSame($feeType->id, (int) collect($preview->viewData('importMappings'))->first());
        $this->assertDatabaseCount('other_payments', 0);

        $this->post('/keuangan/pembayaran/lain-lain/import', ['token' => $preview->viewData('importToken')])
            ->assertRedirect('/keuangan/pembayaran/lain-lain')->assertSessionHas('success');

        $this->assertDatabaseCount('other_payments', 2);
        $this->assertSame(6500000, OtherPayment::sum('paid_amount'));
        $this->assertDatabaseHas('other_payments', ['paid_amount' => 4500000, 'remaining_amount' => 0, 'payment_status' => 'Lunas', 'operator_name' => 'Ziidan Amani']);
    }

    public function test_outstanding_bills_are_shown_automatically_from_recorded_payments(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create(['nis' => '7001', 'name' => 'Tagihan Siswa', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'entry_date' => '2025-07-01', 'is_active' => true]);
        SppSetting::create(['education_unit_id' => $unit->id, 'amount' => 600000, 'is_active' => true]);

        FeeType::create([
            'education_unit_id' => $unit->id, 'code' => 'DAFTAR-ULANG', 'name' => 'Daftar Ulang',
            'amount' => 500000, 'period' => 'Tahunan', 'is_active' => true,
        ]);

        $this->post('/keuangan/pembayaran/spp', [
            'transaction_date' => '2026-01-05', 'transaction_time' => '08:30', 'student_id' => $student->id,
            'months' => [1], 'year' => 2026, 'payment_method' => 'Cash', 'status' => 'Diterima', 'paid_amount' => 600000,
        ])->assertRedirect();

        $this->get('/keuangan/tagihan?year=2026&until_month=6')
            ->assertOk()
            ->assertSee('Pantau seluruh kewajiban siswa secara otomatis')
            ->assertSee('Tagihan Siswa')
            ->assertDontSee('Januari 2026')
            ->assertSee('Februari 2026')
            ->assertSee('Juni 2026')
            ->assertSee('Daftar Ulang')
            ->assertSee('Rp 3.500.000');

        $this->assertDatabaseCount('bills', 0);
    }

    public function test_reports_combine_filter_and_export_payments(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create(['nis' => '8001', 'name' => 'Siswa Laporan', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        SppPayment::create([
            'student_id' => $student->id, 'transaction_at' => '2026-06-10 08:00:00', 'payment_method' => 'Cash',
            'status' => 'Diterima', 'original_amount' => 300000, 'discount_amount' => 0, 'total_amount' => 300000,
            'paid_amount' => 300000, 'remaining_amount' => 0, 'payment_status' => 'Lunas',
        ]);
        $feeType = FeeType::create(['education_unit_id' => $unit->id, 'code' => 'BUKU', 'name' => 'Buku', 'amount' => 200000, 'period' => 'Sekali Bayar', 'is_active' => true]);
        OtherPayment::create([
            'student_id' => $student->id, 'fee_type_id' => $feeType->id, 'transaction_at' => '2026-06-11 09:00:00',
            'payment_method' => 'Transfer', 'status' => 'Diterima', 'original_amount' => 200000, 'discount_amount' => 0,
            'total_amount' => 200000, 'paid_amount' => 200000, 'remaining_amount' => 0, 'payment_status' => 'Lunas',
        ]);

        $this->get('/laporan?start_date=2026-06-01&end_date=2026-06-30')
            ->assertOk()->assertSee('Rp 500.000')->assertSee('Siswa Laporan')->assertSee('Buku');
        $this->get('/laporan?start_date=2026-06-01&end_date=2026-06-30&type=spp')
            ->assertOk()->assertSee('Rp 300.000')->assertDontSee('Buku');
        $this->get('/laporan/export?start_date=2026-06-01&end_date=2026-06-30')
            ->assertOk()->assertDownload('laporan-pembayaran-20260601-20260630.csv');
    }

    public function test_application_settings_can_be_saved(): void
    {
        $this->get('/pengaturan')->assertOk()->assertSee('Pengaturan Aplikasi');
        $this->put('/pengaturan', [
            'school_name' => 'Pondok Mambaul Hikmah',
            'school_address' => 'Jalan Pendidikan',
            'school_phone' => '08123456789',
            'school_email' => 'admin@example.com',
            'finance_officer' => 'Bendahara',
            'receipt_footer' => 'Simpan struk ini.',
            'default_payment_method' => 'Transfer',
        ])->assertRedirect('/pengaturan')->assertSessionHas('success');

        $this->assertSame('Pondok Mambaul Hikmah', AppSetting::where('key', 'school_name')->value('value'));
        $this->assertSame('Transfer', AppSetting::where('key', 'default_payment_method')->value('value'));
    }

    public function test_spp_payment_nominal_can_be_corrected_with_audit_history(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create(['nis' => '6001', 'name' => 'Nadia', 'gender' => 'P', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        SppSetting::create(['education_unit_id' => $unit->id, 'amount' => 300000, 'is_active' => true]);

        $this->post('/keuangan/pembayaran/spp', [
            'transaction_date' => '2026-06-12', 'transaction_time' => '08:30', 'student_id' => $student->id,
            'months' => [1, 2], 'year' => 2026, 'payment_method' => 'Cash', 'status' => 'Diterima', 'paid_amount' => 400000,
        ])->assertRedirect();

        $payment = SppPayment::first();
        $this->post('/keuangan/pembayaran/spp/'.$payment->id.'/corrections', [
            'new_paid_amount' => 150000,
            'reason' => 'Salah input nominal',
        ])->assertRedirect('/keuangan/pembayaran/spp');

        $this->assertDatabaseHas('spp_payments', [
            'id' => $payment->id, 'paid_amount' => 150000, 'remaining_amount' => 450000, 'payment_status' => 'Belum Lunas',
        ]);
        $this->assertDatabaseHas('spp_payment_corrections', [
            'spp_payment_id' => $payment->id, 'old_paid_amount' => 400000, 'new_paid_amount' => 150000,
            'refund_amount' => 250000, 'reason' => 'Salah input nominal',
        ]);
        $this->assertDatabaseHas('spp_payment_items', ['spp_payment_id' => $payment->id, 'month' => 1, 'paid_amount' => 150000]);
        $this->assertDatabaseHas('spp_payment_items', ['spp_payment_id' => $payment->id, 'month' => 2, 'paid_amount' => 0]);
        $this->getJson('/keuangan/pembayaran/spp/'.$payment->id)
            ->assertOk()->assertJsonPath('corrections.0.refund_amount', 250000);
        $this->delete('/keuangan/pembayaran/spp/'.$payment->id)->assertSessionHasErrors('transaction');
        $this->assertDatabaseHas('spp_payments', ['id' => $payment->id]);

        $this->post('/keuangan/pembayaran/spp/'.$payment->id.'/corrections', [
            'new_paid_amount' => 200000,
            'reason' => 'Tidak boleh menambah lewat koreksi',
        ])->assertSessionHasErrors('new_paid_amount');
    }

    public function test_student_can_be_updated_and_deleted(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create([
            'nis' => '1001', 'name' => 'Nama Lama', 'gender' => 'L', 'school_class_id' => $class->id,
            'academic_year_id' => $year->id, 'is_active' => true,
        ]);

        $this->put("/master-data/students/{$student->id}", [
            'nis' => '1001', 'name' => 'Nama Baru', 'gender' => 'L', 'education_unit_id' => $unit->id, 'school_class_id' => $class->id,
            'academic_year_id' => $year->id, 'entry_date' => '2026-06-11', 'is_active' => 1,
        ])->assertRedirect();
        $this->assertDatabaseHas('students', ['name' => 'Nama Baru']);

        $this->delete("/master-data/students/{$student->id}")->assertRedirect();
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    public function test_education_unit_page_is_empty_without_seeded_data(): void
    {
        $this->get('/master-data?tab=education-units')
            ->assertOk()
            ->assertSee('Unit Pendidikan')
            ->assertSee('Belum ada data');
    }

    public function test_students_can_be_exported_and_imported_from_xlsx(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);

        $path = tempnam(sys_get_temp_dir(), 'student-test-');
        StudentXlsx::write($path, [
            ['No', 'NIS', 'NISN', 'Nama', 'Tempat Lahir', 'Tanggal Lahir', 'Jenis Kelamin', 'Nama Ayah', 'Nama Ibu', 'No. WA Ayah', 'No. WA Ibu', 'Provinsi', 'Kabupaten/Kota', 'Kecamatan', 'Desa', 'Alamat', 'Unit Pendidikan', 'Kelas'],
            [1, '1001', '2001', 'Alya Maharani', 'Jakarta', '37209', 'Perempuan', 'Budi', 'Siti', '0811', '0822', 'Jawa Barat', 'Bandung', 'Coblong', 'Dago', 'Jalan Mawar', 'PONPES', '9A'],
        ]);
        $workbook = file_get_contents($path);

        $this->post('/master-data/students/import', [
            'file' => UploadedFile::fake()->createWithContent('siswa.xlsx', $workbook),
        ])->assertRedirect();
        unlink($path);

        $this->assertDatabaseHas('students', [
            'nis' => '1001',
            'father_name' => 'Budi',
            'mother_name' => 'Siti',
            'birth_date' => '2001-11-14 00:00:00',
        ]);
        $this->assertDatabaseHas('school_classes', ['education_unit_id' => $unit->id, 'name' => '9A']);

        $this->post('/master-data/students/import', [
            'file' => UploadedFile::fake()->createWithContent('siswa.xlsx', $workbook),
        ])->assertRedirect();
        $this->assertDatabaseCount('students', 1);

        $this->get('/master-data/students/export')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->get('/master-data/students/template')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_student_nis_must_be_unique(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        Student::create(['nis' => '1001', 'name' => 'Siswa Lama', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $year->id]);

        $this->post('/master-data/students', [
            'nis' => '1001', 'name' => 'Siswa Baru', 'gender' => 'P',
            'education_unit_id' => $unit->id, 'school_class_id' => $class->id, 'academic_year_id' => $year->id,
            'entry_date' => '2026-06-11', 'is_active' => 1,
        ])->assertSessionHasErrors('nis');

        $this->assertDatabaseCount('students', 1);
    }

    public function test_inactive_student_requires_exit_date_and_reason(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);

        $payload = [
            'nis' => '1002', 'name' => 'Siswa Keluar', 'gender' => 'L',
            'education_unit_id' => $unit->id, 'school_class_id' => $class->id,
            'academic_year_id' => $year->id, 'entry_date' => '2025-07-01',
        ];

        $this->post('/master-data/students', $payload)
            ->assertSessionHasErrors(['exit_date', 'inactive_reason']);

        $this->post('/master-data/students', $payload + [
            'exit_date' => '2026-06-01',
            'inactive_reason' => 'Lulus',
        ])->assertRedirect();

        $this->assertDatabaseHas('students', [
            'nis' => '1002', 'is_active' => false, 'exit_date' => '2026-06-01 00:00:00', 'inactive_reason' => 'Lulus',
        ]);
    }
}
