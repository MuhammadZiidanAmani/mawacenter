<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\EducationUnit;
use App\Models\FeeType;
use App\Models\OtherPayment;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::updateOrCreate(['key' => 'admin'], [
            'name' => 'Admin',
            'permissions' => Role::defaultPermissions(),
            'is_active' => true,
        ]);
    }

    public function test_payment_menu_has_transaction_history_and_import_submenus(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/')
            ->assertOk()
            ->assertSee('Pembayaran')
            ->assertSee('Transaksi Baru')
            ->assertSee('Riwayat Pembayaran')
            ->assertSee('Import Pembayaran');
    }

    public function test_payment_history_page_has_clear_links_for_each_payment_group(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/keuangan/pembayaran/riwayat')
            ->assertOk()
            ->assertSee('Riwayat Pembayaran')
            ->assertSee('Riwayat SPP')
            ->assertSee('Riwayat Daftar Ulang')
            ->assertSee('Riwayat Laundry')
            ->assertSee('Riwayat Lain-lain')
            ->assertSee(route('finance.other.index', ['category' => 'daftar-ulang']), false)
            ->assertSee(route('finance.other.index', ['category' => 'laundry']), false)
            ->assertSee(route('finance.other.index'), false)
            ->assertDontSee('Riwayat Lainnya');
    }

    public function test_transaction_hub_stays_focused_without_history_buttons(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/keuangan/pembayaran')
            ->assertOk()
            ->assertSee('Transaksi Baru')
            ->assertSee('Cari siswa')
            ->assertDontSee('Riwayat SPP')
            ->assertDontSee('Riwayat Daftar Ulang')
            ->assertDontSee('Riwayat Laundry')
            ->assertDontSee('Riwayat Lain-lain');
    }

    public function test_transfer_payment_requires_transfer_proof(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create([
            'nis' => '3000', 'name' => 'Siswa Transfer', 'gender' => 'L',
            'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->from(route('finance.payments.index'))
            ->post(route('finance.payments.store'), [
                'student_id' => $student->id,
                'search' => $student->name,
                'payment_method' => 'Transfer',
                'paid_amount' => 100000,
            ])
            ->assertRedirect(route('finance.payments.index'))
            ->assertSessionHasErrors([
                'transfer_proof' => 'Bukti transfer wajib diunggah untuk metode pembayaran Transfer.',
            ]);

        $this->assertDatabaseCount('spp_payments', 0);
        $this->assertDatabaseCount('other_payments', 0);
    }

    public function test_selecting_a_student_shows_their_payment_history_for_the_selected_month_below_the_form(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $selectedStudent = Student::create([
            'nis' => '3001', 'name' => 'Siswa Terpilih', 'gender' => 'L',
            'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true,
        ]);
        $otherStudent = Student::create([
            'nis' => '3002', 'name' => 'Siswa Lain', 'gender' => 'P',
            'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true,
        ]);
        $feeType = FeeType::create([
            'education_unit_id' => $unit->id, 'school_class_id' => $class->id,
            'academic_year_id' => $year->id, 'payment_group' => 'lain-lain', 'code' => 'BUKU',
            'name' => 'Pembayaran Buku', 'amount' => 200000, 'period' => 'Sekali Bayar', 'is_active' => true,
        ]);
        $selectedPayment = OtherPayment::create([
            'student_id' => $selectedStudent->id, 'fee_type_id' => $feeType->id,
            'transaction_at' => '2026-07-05 09:00:00', 'payment_method' => 'Cash', 'status' => 'Diterima',
            'original_amount' => 200000, 'total_amount' => 200000, 'paid_amount' => 200000,
            'remaining_amount' => 0, 'payment_status' => 'Lunas',
        ]);
        foreach (range(1, 10) as $minute) {
            OtherPayment::create([
                'student_id' => $selectedStudent->id, 'fee_type_id' => $feeType->id,
                'transaction_at' => sprintf('2026-07-05 09:%02d:00', $minute),
                'payment_method' => 'Cash', 'status' => 'Diterima',
                'original_amount' => 200000, 'total_amount' => 200000, 'paid_amount' => 200000,
                'remaining_amount' => 0, 'payment_status' => 'Lunas',
            ]);
        }
        $olderPayment = OtherPayment::create([
            'student_id' => $selectedStudent->id, 'fee_type_id' => $feeType->id,
            'transaction_at' => '2026-06-30 09:00:00', 'payment_method' => 'Cash', 'status' => 'Diterima',
            'original_amount' => 200000, 'total_amount' => 200000, 'paid_amount' => 200000,
            'remaining_amount' => 0, 'payment_status' => 'Lunas',
        ]);
        $otherPayment = OtherPayment::create([
            'student_id' => $otherStudent->id, 'fee_type_id' => $feeType->id,
            'transaction_at' => '2026-07-05 10:00:00', 'payment_method' => 'Transfer', 'status' => 'Diterima',
            'original_amount' => 200000, 'total_amount' => 200000, 'paid_amount' => 200000,
            'remaining_amount' => 0, 'payment_status' => 'Lunas',
        ]);

        $returnUrl = route('finance.payments.index', [
            'search' => $selectedStudent->name,
            'student_id' => $selectedStudent->id,
            'history_period' => '2026-07',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.payments.index', [
                'search' => $selectedStudent->name,
                'history_period' => '2026-07',
            ]))
            ->assertOk()
            ->assertSeeInOrder([
                'payment-one-stop-profile-card',
                'payment-one-stop-pay-form',
                'payment-one-stop-history-card',
            ], false)
            ->assertSee('Riwayat Pembayaran')
            ->assertDontSee('11 transaksi')
            ->assertDontSee('>Periode<', false)
            ->assertSee('name="history_period" value="2026-07"', false)
            ->assertSee(route('finance.other.receipt', $selectedPayment), false)
            ->assertSee('name="return_url" value="'.e($returnUrl).'"', false)
            ->assertDontSee(route('finance.other.receipt', $olderPayment), false)
            ->assertDontSee(route('finance.other.receipt', $otherPayment), false);

        $this->delete(route('finance.other.destroy', $selectedPayment), ['return_url' => $returnUrl])
            ->assertRedirect($returnUrl);
        $this->assertDatabaseMissing('other_payments', ['id' => $selectedPayment->id]);
    }

    public function test_optional_only_bills_show_paid_administration_without_a_second_divider(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-07-05 10:00:00'));

        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'Asrama A', 'level' => 'Asrama']);
        $student = Student::create([
            'nis' => '4001', 'name' => 'Siswa Opsional', 'gender' => 'L',
            'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true,
        ]);
        FeeType::create([
            'education_unit_id' => $unit->id, 'school_class_id' => $class->id,
            'academic_year_id' => $year->id, 'payment_group' => 'laundry', 'code' => 'LAUNDRY-OPSIONAL',
            'name' => 'Laundry Bulanan', 'amount' => 110000, 'period' => 'Bulanan',
            'creates_bill' => false, 'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.payments.index', [
                'search' => $student->name,
                'history_period' => '2026-07',
            ]))
            ->assertOk()
            ->assertSee('Lunas Administrasi')
            ->assertDontSee('1 Tagihan')
            ->assertSee('payment-one-stop-optional-section is-only-optional', false)
            ->assertSee('1 Pilihan')
            ->assertSee('Juli 2026')
            ->assertDontSee('Januari 2026');
    }

    public function test_transaction_hub_groups_one_student_across_education_units(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $mts = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $ponpes = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $mtsClass = SchoolClass::create(['education_unit_id' => $mts->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $ponpesClass = SchoolClass::create(['education_unit_id' => $ponpes->id, 'name' => 'Asrama A', 'level' => 'Asrama']);
        $identity = Student::create([
            'nis' => 'MTS-001', 'nisn' => '1234567890', 'name' => 'Ahmad Fauzan', 'gender' => 'L',
            'school_class_id' => $mtsClass->id, 'academic_year_id' => $year->id, 'is_active' => true,
        ]);
        $boarding = Student::create([
            'identity_student_id' => $identity->id, 'nis' => 'PP-099', 'nisn' => '1234567890',
            'name' => 'Ahmad Fauzan', 'gender' => 'L', 'school_class_id' => $ponpesClass->id,
            'academic_year_id' => $year->id, 'is_active' => true,
        ]);
        FeeType::create([
            'education_unit_id' => $mts->id, 'school_class_id' => $mtsClass->id,
            'academic_year_id' => $year->id, 'payment_group' => 'spp', 'code' => 'SPP-MTS',
            'name' => 'SPP MTs', 'amount' => 300000, 'period' => 'Bulanan', 'is_active' => true,
        ]);
        FeeType::create([
            'education_unit_id' => $ponpes->id, 'school_class_id' => $ponpesClass->id,
            'academic_year_id' => $year->id, 'payment_group' => 'laundry', 'code' => 'LAUNDRY-PP',
            'name' => 'Laundry Ponpes', 'amount' => 100000, 'period' => 'Bulanan', 'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/keuangan/pembayaran?search=Ahmad')
            ->assertOk()
            ->assertSee('AHMAD FAUZAN')
            ->assertDontSee('2 unit')
            ->assertSee('MTS-001')
            ->assertSee('PP-099')
            ->assertSee(route('finance.spp.create', ['student_id' => $identity->id]), false)
            ->assertSee(route('finance.other.create', ['category' => 'laundry', 'student_id' => $boarding->id]));

        $this->get(route('finance.spp.create', ['student_id' => $identity->id]))
            ->assertOk()
            ->assertSee('value="'.$identity->id.'" selected', false);
        $this->get(route('finance.other.create', ['category' => 'laundry', 'student_id' => $boarding->id]))
            ->assertOk()
            ->assertSee('value="'.$boarding->id.'"', false)
            ->assertSee('selected', false);
    }

    public function test_central_payment_import_page_uses_existing_importers(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/keuangan/pembayaran/import')
            ->assertOk()
            ->assertSee('Import Pembayaran')
            ->assertSee(route('finance.spp.import.preview'), false)
            ->assertSee(route('finance.other.import.preview', ['category' => 'daftar-ulang']), false)
            ->assertSee(route('finance.other.import.preview', ['category' => 'laundry']), false)
            ->assertSee('Upload dan Preview');
    }

    public function test_registration_and_laundry_payment_sections_are_accessible(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/keuangan/pembayaran/lain-lain?category=daftar-ulang')
            ->assertOk()
            ->assertSee('Pembayaran Daftar Ulang')
            ->assertSee('name="date_from" value="'.now()->startOfMonth()->toDateString().'"', false);

        $this->actingAs($user)->get('/keuangan/pembayaran/lain-lain?category=laundry')
            ->assertOk()
            ->assertSee('Pembayaran Laundry');
    }

    public function test_payment_section_rejects_fee_type_from_another_group(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '7A', 'level' => 'Kelas 7']);
        $student = Student::create([
            'nis' => '1001',
            'name' => 'Siswa',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $laundry = FeeType::create([
            'education_unit_id' => $unit->id,
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'laundry',
            'code' => 'LAUNDRY-7A',
            'name' => 'Laundry 7A',
            'amount' => 150000,
            'period' => 'Bulanan',
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson('/keuangan/pembayaran/lain-lain/quote?category=daftar-ulang&student_id='.$student->id.'&fee_type_id='.$laundry->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fee_type_id');
    }

    public function test_laundry_payment_uses_monthly_flow_like_spp(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-01-16 09:00:00'));

        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '7A', 'level' => 'Kelas 7']);
        $student = Student::create([
            'nis' => '2001',
            'name' => 'Siswa Laundry',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $laundry = FeeType::create([
            'education_unit_id' => $unit->id,
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'laundry',
            'code' => 'LAUNDRY-7A',
            'name' => 'Laundry 7A',
            'amount' => 150000,
            'period' => 'Bulanan',
            'is_active' => true,
        ]);
        $this->actingAs(User::factory()->create());

        $this->get('/keuangan/pembayaran/lain-lain/create?category=laundry')
            ->assertOk()
            ->assertSee('data-laundry-form', false)
            ->assertSee('data-laundry-month-values', false)
            ->assertSee('Biaya / Bulan');

        $this->getJson('/keuangan/pembayaran/lain-lain/months?category=laundry&student_id='.$student->id.'&fee_type_id='.$laundry->id.'&year=2026')
            ->assertOk()
            ->assertJsonPath('first_payable_month', 1)
            ->assertJsonPath('months.0.payment_status', 'Belum Dibayar');

        $this->getJson('/keuangan/pembayaran/lain-lain/quote?category=laundry&student_id='.$student->id.'&fee_type_id='.$laundry->id.'&year=2026&months[]=1&months[]=2')
            ->assertOk()
            ->assertJson([
                'original_amount' => 300000,
                'remaining_amount' => 300000,
            ]);

        $this->post('/keuangan/pembayaran/lain-lain?category=laundry', [
            'transaction_date' => '16/06/2026',
            'transaction_time' => '10.00',
            'student_id' => $student->id,
            'fee_type_id' => $laundry->id,
            'year' => 2026,
            'months' => [1, 2],
            'payment_method' => 'Cash',
            'status' => 'Diterima',
            'paid_amount' => 200000,
        ])->assertRedirect('/keuangan/pembayaran/lain-lain?category=laundry');

        $payment = OtherPayment::firstOrFail();
        $this->assertDatabaseHas('other_payment_items', [
            'other_payment_id' => $payment->id,
            'month' => 1,
            'paid_amount' => 150000,
            'payment_status' => 'Lunas',
        ]);
        $this->assertDatabaseHas('other_payment_items', [
            'other_payment_id' => $payment->id,
            'month' => 2,
            'paid_amount' => 50000,
            'remaining_amount' => 100000,
        ]);

        $this->get('/keuangan/pembayaran/lain-lain?category=laundry&date_from=2026-06-16&date_to=2026-06-16')
            ->assertOk()
            ->assertSee('Januari 2026, Februari 2026')
            ->assertSee('data-other-edit-url="'.route('finance.other.show', $payment).'"', false)
            ->assertSee('data-other-delete-url="'.route('finance.other.destroy', $payment).'"', false);

        $this->travelTo(CarbonImmutable::parse('2026-03-01 09:00:00'));
        $this->getJson('/keuangan/pembayaran/lain-lain/quote?category=laundry&student_id='.$student->id.'&fee_type_id='.$laundry->id.'&year=2026&months[]=2')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('months')
            ->assertJsonPath('errors.months.0', 'Pembayaran Laundry untuk bulan sebelumnya sudah ditutup. Pilih bulan berjalan atau bulan berikutnya.');
    }

    public function test_transaction_hub_can_pay_spp_through_a_selected_future_month(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-07-05 10:00:00'));

        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '7A', 'level' => 'Kelas 7']);
        $student = Student::create([
            'nis' => 'SPP-12',
            'name' => 'Siswa SPP Tahunan',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'billing_start_date' => '2026-07-01',
            'is_active' => true,
        ]);
        FeeType::create([
            'education_unit_id' => $unit->id,
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'spp',
            'code' => 'SPP-7A',
            'name' => 'SPP 7A',
            'amount' => 300000,
            'period' => 'Bulanan',
            'creates_bill' => true,
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create());
        $this->get('/keuangan/pembayaran?search='.urlencode($student->name))
            ->assertOk()
            ->assertSee('Bayar sampai')
            ->assertSee('Juli 2026 - Desember 2026');

        $this->post('/keuangan/pembayaran', [
            'student_id' => $student->id,
            'search' => $student->name,
            'bill_keys' => [$student->id.':spp'],
            'payment_month_counts' => [$student->id.'_spp' => 6],
            'payment_method' => 'Cash',
            'paid_amount' => 1800000,
        ])
            ->assertRedirect();

        $this->assertDatabaseCount('spp_payments', 1);
        $this->assertDatabaseCount('spp_payment_items', 6);
        $this->assertDatabaseHas('spp_payment_items', ['year' => 2026, 'month' => 7, 'paid_amount' => 300000]);
        $this->assertDatabaseHas('spp_payment_items', ['year' => 2026, 'month' => 12, 'paid_amount' => 300000]);
    }

    public function test_transaction_hub_can_pay_laundry_for_two_consecutive_months(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-07-05 10:00:00'));

        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '7A', 'level' => 'Kelas 7']);
        $student = Student::create([
            'nis' => 'LD-12',
            'name' => 'Siswa Laundry Tahunan',
            'gender' => 'P',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $laundry = FeeType::create([
            'education_unit_id' => $unit->id,
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'laundry',
            'code' => 'LAUNDRY-7A',
            'name' => 'Laundry 7A',
            'amount' => 100000,
            'period' => 'Bulanan',
            'creates_bill' => false,
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create());
        $this->get('/keuangan/pembayaran?search='.urlencode($student->name))
            ->assertOk()
            ->assertSee('Bayar sampai')
            ->assertSee('Juli 2026 - Agustus 2026');

        $this->post('/keuangan/pembayaran', [
            'student_id' => $student->id,
            'search' => $student->name,
            'optional_keys' => [$student->id.':optional:'.$laundry->id],
            'payment_month_counts' => [$student->id.'_optional_'.$laundry->id => 2],
            'payment_method' => 'Cash',
            'paid_amount' => 200000,
        ])
            ->assertRedirect();

        $this->assertDatabaseCount('other_payments', 1);
        $this->assertDatabaseCount('other_payment_items', 2);
        $this->assertDatabaseHas('other_payment_items', ['year' => 2026, 'month' => 7, 'paid_amount' => 100000]);
        $this->assertDatabaseHas('other_payment_items', ['year' => 2026, 'month' => 8, 'paid_amount' => 100000]);
    }

    public function test_registration_payment_pending_does_not_reduce_the_remaining_charge(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '7A', 'level' => 'Kelas 7']);
        $student = Student::create([
            'nis' => '1002',
            'name' => 'Siswa Daftar Ulang',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $feeType = FeeType::create([
            'education_unit_id' => $unit->id,
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'daftar-ulang',
            'code' => 'DAFTAR-ULANG-7A',
            'name' => 'Daftar Ulang 7A',
            'amount' => 1000000,
            'period' => 'Sekali Bayar',
            'is_active' => true,
        ]);
        $this->actingAs(User::factory()->create());

        $this->get('/keuangan/pembayaran/lain-lain/create?category=daftar-ulang')
            ->assertOk()
            ->assertSee('data-payment-category="daftar-ulang"', false)
            ->assertSee('Total Bayar')
            ->assertDontSee('Nominal Dibayar Sekarang')
            ->assertSee('>Daftar Ulang 7A</option>', false)
            ->assertDontSee('Daftar Ulang 7A · PONPES', false);

        $studentSearch = 'PONPES - 1002 - Siswa Daftar Ulang';
        $this->getJson('/keuangan/pembayaran/lain-lain/quote?category=daftar-ulang&student_search='.urlencode($studentSearch).'&fee_type_id='.$feeType->id)
            ->assertOk()
            ->assertJson([
                'original_amount' => 1000000,
                'remaining_amount' => 1000000,
            ]);

        $this->post('/keuangan/pembayaran/lain-lain?category=daftar-ulang', [
            'transaction_date' => '2026-06-14',
            'transaction_time' => '09:00:00',
            'student_search' => $studentSearch,
            'fee_type_id' => $feeType->id,
            'payment_method' => 'Cash',
            'status' => 'Pending',
            'paid_amount' => 400000,
        ])->assertRedirect('/keuangan/pembayaran/lain-lain?category=daftar-ulang');

        $pending = OtherPayment::firstOrFail();
        $this->assertSame('Pending', $pending->payment_status);
        $this->assertSame(1000000, $pending->remaining_amount);

        $this->getJson('/keuangan/pembayaran/lain-lain/quote?category=daftar-ulang&student_id='.$student->id.'&fee_type_id='.$feeType->id)
            ->assertOk()
            ->assertJson([
                'paid_amount' => 0,
                'remaining_amount' => 1000000,
            ]);
        $this->get('/laporan?start_date=2026-06-14&end_date=2026-06-14&type=daftar-ulang')
            ->assertOk()
            ->assertSee('Siswa Daftar Ulang')
            ->assertSee('Pending')
            ->assertSee('<div class="total"><span>Rp 0</span><small>0 diterima</small></div>', false);

        $this->post('/keuangan/pembayaran/lain-lain?category=daftar-ulang', [
            'transaction_date' => '2026-06-14',
            'transaction_time' => '10:00:00',
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'payment_method' => 'Transfer',
            'status' => 'Diterima',
            'paid_amount' => 600000,
        ])->assertRedirect('/keuangan/pembayaran/lain-lain?category=daftar-ulang');

        $this->getJson('/keuangan/pembayaran/lain-lain/quote?category=daftar-ulang&student_id='.$student->id.'&fee_type_id='.$feeType->id)
            ->assertOk()
            ->assertJson([
                'paid_amount' => 600000,
                'remaining_amount' => 400000,
            ]);

        $payment = OtherPayment::latest('id')->firstOrFail();
        $this->get('/keuangan/pembayaran/lain-lain?category=daftar-ulang&date_from=2026-06-14&date_to=2026-06-14')
            ->assertOk()
            ->assertSee(route('finance.other.receipt', $payment), false)
            ->assertSee('title="Cetak Struk"', false)
            ->assertSee('Unit Pendidikan: PONPES')
            ->assertSee('registration-payment-table', false)
            ->assertSee('Unit Pendidikan')
            ->assertSee('class="registration-payment-detail"', false)
            ->assertSee('Kategori Pembayaran')
            ->assertDontSee('<span>Kategori</span>', false)
            ->assertSee('Cara Bayar')
            ->assertSee('Petugas')
            ->assertSee('data-other-edit-url="'.route('finance.other.show', $payment).'"', false)
            ->assertSee('data-other-delete-url="'.route('finance.other.destroy', $payment).'"', false);

        $this->get(route('finance.other.show', $payment))
            ->assertOk()
            ->assertJsonPath('student_name', 'Siswa Daftar Ulang')
            ->assertJsonPath('payment_method', 'Transfer');

        $this->put(route('finance.other.update', $payment), [
            'transaction_date' => '15/06/2026',
            'transaction_time' => '11.30',
            'payment_method' => 'Cash',
            'status' => 'Pending',
        ])->assertRedirect('/keuangan/pembayaran/lain-lain?category=daftar-ulang');
        $this->assertDatabaseHas('other_payments', [
            'id' => $payment->id,
            'transaction_at' => '2026-06-15 11:30:00',
            'payment_method' => 'Cash',
            'status' => 'Pending',
            'payment_status' => 'Pending',
        ]);
        $this->getJson('/keuangan/pembayaran/lain-lain/quote?category=daftar-ulang&student_id='.$student->id.'&fee_type_id='.$feeType->id)
            ->assertOk()
            ->assertJson([
                'paid_amount' => 0,
                'remaining_amount' => 1000000,
            ]);

        $this->get(route('finance.other.receipt', $payment))
            ->assertOk()
            ->assertHeader('content-type', 'text/html; charset=UTF-8')
            ->assertSee('Kwitansi Pembayaran')
            ->assertSee('DU-20260615-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT))
            ->assertSee('@page { size: A4 portrait; margin: 0; }', false)
            ->assertSee('Tahun Pelajaran')
            ->assertSee('Keringanan (Rp)')
            ->assertSee("window.addEventListener('load', () => window.print())", false);

        $this->delete(route('finance.other.destroy', $payment))
            ->assertRedirect('/keuangan/pembayaran/lain-lain?category=daftar-ulang');
        $this->assertDatabaseMissing('other_payments', ['id' => $payment->id]);
    }

    public function test_registration_payment_can_use_future_inactive_academic_year(): void
    {
        $currentYear = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $futureYear = AcademicYear::create(['name' => '2026/2027', 'is_active' => false]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create([
            'nis' => '2601',
            'name' => 'Siswa Tahun Depan',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $currentYear->id,
            'is_active' => true,
        ]);
        $feeType = FeeType::create([
            'education_unit_id' => $unit->id,
            'school_class_id' => $class->id,
            'academic_year_id' => $futureYear->id,
            'payment_group' => 'daftar-ulang',
            'code' => 'DAFTAR-ULANG-2026-VII-A',
            'name' => 'Daftar Ulang 2026/2027 VII A',
            'amount' => 1200000,
            'period' => 'Sekali Bayar',
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create());

        $this->get('/keuangan/pembayaran?search=Tahun%20Depan')
            ->assertOk()
            ->assertSee('Daftar Ulang');

        $this->get('/keuangan/pembayaran/lain-lain/create?category=daftar-ulang&academic_year_id='.$futureYear->id.'&student_id='.$student->id)
            ->assertOk()
            ->assertSee('Tahun Pelajaran')
            ->assertSee('2026/2027')
            ->assertSee('Daftar Ulang 2026/2027 VII A');

        $this->getJson('/keuangan/pembayaran/lain-lain/quote?category=daftar-ulang&student_id='.$student->id.'&fee_type_id='.$feeType->id)
            ->assertOk()
            ->assertJson([
                'original_amount' => 1200000,
                'remaining_amount' => 1200000,
            ]);

        $this->post('/keuangan/pembayaran/lain-lain?category=daftar-ulang', [
            'transaction_date' => '2026-06-24',
            'transaction_time' => '09:00:00',
            'student_id' => $student->id,
            'academic_year_id' => $futureYear->id,
            'fee_type_id' => $feeType->id,
            'payment_method' => 'Cash',
            'status' => 'Diterima',
            'paid_amount' => 1200000,
        ])->assertRedirect('/keuangan/pembayaran/lain-lain?category=daftar-ulang');

        $this->assertDatabaseHas('other_payments', [
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'paid_amount' => 1200000,
            'payment_status' => 'Lunas',
        ]);
        $this->assertDatabaseHas('bills', [
            'student_id' => $student->id,
            'academic_year_id' => $futureYear->id,
            'source_type' => 'fee_type',
            'fee_type_id' => $feeType->id,
            'status' => 'Lunas',
        ]);
    }

    public function test_registration_payment_rejects_category_for_another_class(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $studentClass = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $otherClass = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII B', 'level' => 'Kelas VII']);
        $student = Student::create([
            'nis' => '1003',
            'name' => 'Siswa VII A',
            'gender' => 'P',
            'school_class_id' => $studentClass->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $feeType = FeeType::create([
            'education_unit_id' => $unit->id,
            'school_class_id' => $otherClass->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'daftar-ulang',
            'code' => 'DAFTAR-ULANG-VII-B',
            'name' => 'Daftar Ulang VII B',
            'amount' => 900000,
            'period' => 'Sekali Bayar',
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson('/keuangan/pembayaran/lain-lain/quote?category=daftar-ulang&student_id='.$student->id.'&fee_type_id='.$feeType->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fee_type_id');
    }
}
