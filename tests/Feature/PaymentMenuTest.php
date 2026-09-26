<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Bill;
use App\Models\EducationUnit;
use App\Models\FeeType;
use App\Models\OtherPayment;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SppPayment;
use App\Models\SppPaymentItem;
use App\Models\Student;
use App\Models\User;
use App\Services\BillService;
use App\Services\GoogleDriveStorageException;
use App\Services\GoogleDriveStorageService;
use App\Services\OtherPaymentService;
use App\Support\StudentXlsx;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
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

    public function test_pending_spp_does_not_reduce_bills_until_it_is_accepted(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-07-15 09:00:00'));
        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '1A', 'level' => 1]);
        $student = Student::create([
            'nis' => 'PENDING-SPP',
            'name' => 'Siswa Pending SPP',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'billing_start_date' => '2026-07-01',
            'is_active' => true,
        ]);
        FeeType::create([
            'education_unit_id' => $unit->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'spp',
            'code' => 'SPP-PENDING',
            'name' => 'SPP Pending',
            'amount' => 100000,
            'period' => 'Bulanan',
            'creates_bill' => true,
            'is_active' => true,
        ]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post(route('finance.spp.store'), [
            'transaction_date' => '2026-07-15',
            'transaction_time' => '09:00:00',
            'student_id' => $student->id,
            'month_count' => 1,
            'payment_method' => 'Transfer',
            'status' => 'Pending',
            'paid_amount' => 100000,
        ])->assertRedirect();

        $payment = SppPayment::firstOrFail();
        $this->assertDatabaseHas('spp_payments', [
            'id' => $payment->id,
            'paid_amount' => 100000,
            'remaining_amount' => 100000,
            'payment_status' => 'Pending',
        ]);
        $this->assertDatabaseHas('spp_payment_items', [
            'spp_payment_id' => $payment->id,
            'paid_amount' => 0,
            'remaining_amount' => 100000,
            'payment_status' => 'Pending',
        ]);
        $this->assertDatabaseMissing('bill_payment_allocations', [
            'payment_type' => 'spp',
            'payment_id' => $payment->id,
        ]);
        $this->getJson(route('finance.spp.quote', ['student_id' => $student->id, 'month_count' => 1]))
            ->assertOk()
            ->assertJson(['paid_amount' => 0, 'remaining_amount' => 100000]);

        $this->put(route('finance.spp.update', $payment), [
            'transaction_date' => '2026-07-15',
            'transaction_time' => '09:00:00',
            'payment_method' => 'Cash',
            'status' => 'Diterima',
            'paid_amount' => 100000,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $bill = Bill::where('student_id', $student->id)->where('source_type', 'spp')->firstOrFail();
        $this->assertDatabaseHas('spp_payment_items', ['spp_payment_id' => $payment->id, 'paid_amount' => 100000]);
        $this->assertDatabaseHas('bills', ['id' => $bill->id, 'paid_amount' => 100000, 'remaining_amount' => 0, 'status' => 'Lunas']);

        $this->put(route('finance.spp.update', $payment), [
            'transaction_date' => '2026-07-15',
            'transaction_time' => '09:00:00',
            'payment_method' => 'Cash',
            'status' => 'Pending',
            'paid_amount' => 100000,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('spp_payment_items', ['spp_payment_id' => $payment->id, 'paid_amount' => 0, 'payment_status' => 'Pending']);
        $this->assertDatabaseHas('bills', ['id' => $bill->id, 'paid_amount' => 0, 'remaining_amount' => 100000, 'status' => 'Belum Dibayar']);
    }

    public function test_pending_laundry_does_not_reduce_the_next_payable_amount(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-07-15 09:00:00'));
        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'Asrama A', 'level' => 'Asrama']);
        $student = Student::create([
            'nis' => 'PENDING-LAUNDRY',
            'name' => 'Siswa Pending Laundry',
            'gender' => 'P',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $feeType = FeeType::create([
            'education_unit_id' => $unit->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'laundry',
            'code' => 'LAUNDRY-PENDING',
            'name' => 'Laundry Pending',
            'amount' => 75000,
            'period' => 'Bulanan',
            'creates_bill' => false,
            'is_active' => true,
        ]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post(route('finance.other.store', ['category' => 'laundry']), [
            'category' => 'laundry',
            'transaction_date' => '2026-07-15',
            'transaction_time' => '09:00:00',
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'year' => 2026,
            'months' => [7],
            'payment_method' => 'Transfer',
            'status' => 'Pending',
            'paid_amount' => 75000,
        ])->assertRedirect();

        $payment = OtherPayment::firstOrFail();
        $this->assertDatabaseHas('other_payments', [
            'id' => $payment->id,
            'remaining_amount' => 75000,
            'payment_status' => 'Pending',
        ]);
        $this->assertDatabaseHas('other_payment_items', [
            'other_payment_id' => $payment->id,
            'paid_amount' => 0,
            'remaining_amount' => 75000,
            'payment_status' => 'Pending',
        ]);
        $this->getJson(route('finance.other.quote', [
            'category' => 'laundry',
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'year' => 2026,
            'months' => [7],
        ]))->assertOk()->assertJson(['paid_amount' => 0, 'remaining_amount' => 75000]);

        $this->put(route('finance.other.update', $payment), [
            'transaction_date' => '2026-07-15',
            'transaction_time' => '09:00:00',
            'payment_method' => 'Transfer',
            'status' => 'Diterima',
        ])->assertRedirect();

        $this->assertDatabaseHas('other_payment_items', ['other_payment_id' => $payment->id, 'paid_amount' => 75000]);
        $this->getJson(route('finance.other.quote', [
            'category' => 'laundry',
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'year' => 2026,
            'months' => [7],
        ]))->assertUnprocessable();
    }

    public function test_bulk_payment_rolls_back_every_record_and_uploaded_proof_when_one_item_fails(): void
    {
        config(['payments.transfer_proof_upload_enabled' => true]);
        Storage::fake('local');
        $this->travelTo(CarbonImmutable::parse('2026-07-15 09:00:00'));
        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '1A', 'level' => 1]);
        $student = Student::create([
            'nis' => 'ATOMIC-001',
            'name' => 'Siswa Pembayaran Atomik',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'billing_start_date' => '2026-07-01',
            'is_active' => true,
        ]);
        FeeType::create([
            'education_unit_id' => $unit->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'spp',
            'code' => 'SPP-ATOMIC',
            'name' => 'SPP Atomic',
            'amount' => 100000,
            'period' => 'Bulanan',
            'creates_bill' => true,
            'is_active' => true,
        ]);
        $optional = FeeType::create([
            'education_unit_id' => $unit->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'lain-lain',
            'code' => 'OTHER-ATOMIC',
            'name' => 'Pembayaran Gagal',
            'amount' => 50000,
            'period' => 'Sekali Bayar',
            'creates_bill' => false,
            'is_active' => true,
        ]);
        $otherPayments = Mockery::mock(OtherPaymentService::class);
        $otherPayments->shouldReceive('quote')->twice()->andReturn([
            'original_amount' => 50000,
            'discount_amount' => 0,
            'total_amount' => 50000,
            'paid_amount' => 0,
            'remaining_amount' => 50000,
            'payment_status' => 'Belum Lunas',
        ]);
        $otherPayments->shouldReceive('record')->once()->andThrow(new RuntimeException('Simulasi transaksi kedua gagal.'));
        $this->app->instance(OtherPaymentService::class, $otherPayments);
        $drive = Mockery::mock(GoogleDriveStorageService::class);
        $drive->shouldReceive('upload')->once()->andReturn([
            'file_id' => 'drive-atomic-proof',
            'metadata' => ['name' => 'proof.pdf', 'mime_type' => 'application/pdf', 'size' => 12, 'uploaded_at' => '2026-07-15T09:00:00+07:00'],
        ]);
        $drive->shouldReceive('delete')->once()->with('drive-atomic-proof');
        $this->app->instance(GoogleDriveStorageService::class, $drive);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->withoutExceptionHandling();

        try {
            $this->post(route('finance.payments.store'), [
                'student_id' => $student->id,
                'search' => $student->name,
                'bill_keys' => [$student->id.':spp'],
                'optional_keys' => [$student->id.':optional:'.$optional->id],
                'payment_month_counts' => [$student->id.'_spp' => 1],
                'payment_method' => 'Transfer',
                'paid_amount' => 150000,
                'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 12, 'application/pdf'),
            ]);
            $this->fail('Pembayaran gabungan seharusnya gagal.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi transaksi kedua gagal.', $exception->getMessage());
        }

        $this->assertDatabaseCount('spp_payments', 0);
        $this->assertDatabaseCount('spp_payment_items', 0);
        $this->assertDatabaseCount('other_payments', 0);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'payments.bulk_create']);
        $this->assertSame([], Storage::disk('local')->allFiles('payment-proofs'));
    }

    public function test_payment_is_direct_menu_and_reports_use_prd_submenus(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/')
            ->assertOk()
            ->assertSee('<a href="'.route('finance.payments.index').'" class="nav-item ">', false)
            ->assertDontSee('<span>Transaksi Baru</span>', false)
            ->assertDontSee('<span>Import Pembayaran</span>', false)
            ->assertSee('<span>Transaksi Pembayaran</span>', false)
            ->assertSee('<span>SPP Perbulan</span>', false)
            ->assertDontSee('<span>SPP Belum Bayar</span>', false)
            ->assertSee('<span>SPP Pertahun</span>', false)
            ->assertSee('<span>Rekap Per Unit</span>', false)
            ->assertDontSee('<span>Riwayat Pembayaran</span>', false);
    }

    public function test_payment_history_page_has_clear_links_for_each_payment_group(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/keuangan/pembayaran/riwayat')
            ->assertOk()
            ->assertSee('Riwayat Pembayaran')
            ->assertSee('Pilih jenis riwayat untuk melihat transaksi yang sudah tercatat.')
            ->assertSee('Riwayat SPP')
            ->assertSee('Riwayat Daftar Ulang')
            ->assertSee('Riwayat Laundry')
            ->assertSee('Riwayat Lain-lain')
            ->assertSee(route('finance.spp.index'), false)
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
            ->assertSee('<h1>Daftar Pembayaran Siswa</h1>', false)
            ->assertSee('href="'.route('finance.payments.import').'"', false)
            ->assertSee('Import Excel')
            ->assertDontSee('<h1>Transaksi Baru</h1>', false)
            ->assertSee('Cari Siswa')
            ->assertSee('placeholder="Ketik nama siswa atau NIS..."', false)
            ->assertSeeText('Siswa tidak ditemukan')
            ->assertDontSeeText('Belum ada siswa dipilih')
            ->assertDontSee('Cari siswa berdasarkan nama, NIS, atau NISN untuk melihat tagihan pembayaran.')
            ->assertDontSee('Riwayat SPP')
            ->assertDontSee('Riwayat Daftar Ulang')
            ->assertDontSee('Riwayat Laundry')
            ->assertDontSee('Riwayat Lain-lain');
    }

    public function test_selected_student_card_is_compact_and_replace_returns_to_search_mode(): void
    {
        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '11A', 'level' => 11]);
        $student = Student::create([
            'nis' => '220006',
            'name' => 'AHMAD ISA',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)
            ->get(route('finance.payments.index', [
                'search' => $student->name,
                'student_id' => $student->id,
            ]));

        $response
            ->assertOk()
            ->assertSee('class="payment-one-stop-layout payment-prd-layout is-student-selected"', false)
            ->assertSee('class="payment-selected-page-head"', false)
            ->assertSeeText('AHMAD ISA')
            ->assertSeeText('PONPES')
            ->assertSeeText('11A')
            ->assertSee('href="'.route('finance.payments.index').'" class="payment-selected-back" aria-label="Kembali ke daftar pembayaran"', false)
            ->assertDontSee('data-payment-student-nis', false)
            ->assertDontSeeText('Import Excel')
            ->assertDontSee('<h2>Data Siswa</h2>', false)
            ->assertDontSeeText('Siswa dipilih')
            ->assertDontSee('<h2>Cari Siswa</h2>', false)
            ->assertDontSee('placeholder="Cari nama, NIS, atau NISN..."', false);

        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $selectedLayout = '//div[contains(concat(" ", normalize-space(@class), " "), " payment-prd-layout ") and contains(concat(" ", normalize-space(@class), " "), " is-student-selected ")]';

        $this->assertCount(1, $xpath->query('//header[contains(concat(" ", normalize-space(@class), " "), " payment-selected-page-head ")]'));
        $this->assertCount(0, $xpath->query($selectedLayout.'/section[contains(concat(" ", normalize-space(@class), " "), " payment-prd-search-panel ")]'));
        $this->assertCount(1, $xpath->query($selectedLayout.'/form[contains(concat(" ", normalize-space(@class), " "), " payment-one-stop-pay-form ")]'));
        $this->assertCount(1, $xpath->query($selectedLayout.'/form/section[contains(concat(" ", normalize-space(@class), " "), " payment-prd-bill-card ")]'));
        $this->assertCount(1, $xpath->query($selectedLayout.'/form/section[contains(concat(" ", normalize-space(@class), " "), " payment-prd-summary-card ")]'));
        $this->assertCount(1, $xpath->query($selectedLayout.'/section[contains(concat(" ", normalize-space(@class), " "), " payment-one-stop-history-card ")]'));
        $this->assertCount(0, $xpath->query($selectedLayout.'/section[contains(concat(" ", normalize-space(@class), " "), " payment-one-stop-side ")]'));

        $this->actingAs($user)
            ->get(route('finance.payments.index'))
            ->assertOk()
            ->assertSee('placeholder="Ketik nama siswa atau NIS..."', false)
            ->assertDontSee('autofocus', false)
            ->assertDontSee('payment-selected-student-card', false);
    }

    public function test_selected_student_summary_uses_payable_bills_and_excludes_future_bills(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-23 08:00:00'));
        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PAUD', 'name' => 'PAUD Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'A1', 'level' => 'A1', 'is_active' => true]);
        $student = Student::create([
            'nis' => 'SUMMARY-001',
            'name' => 'Siswa Ringkasan',
            'gender' => 'P',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);

        foreach ([
            ['key' => 'summary-active-one', 'total' => 500000, 'paid' => 125000, 'remaining' => 375000, 'status' => 'Sebagian'],
            ['key' => 'summary-active-two', 'total' => 250000, 'paid' => 250000, 'remaining' => 0, 'status' => 'Lunas'],
            ['key' => 'summary-future', 'total' => 50000, 'paid' => 0, 'remaining' => 50000, 'status' => 'Belum Dibayar', 'issue_date' => '2026-10-01', 'due_date' => '2026-10-10'],
            ['key' => 'summary-cancelled', 'total' => 900000, 'paid' => 0, 'remaining' => 900000, 'status' => 'Dibatalkan'],
        ] as $bill) {
            Bill::create([
                'student_id' => $student->id,
                'academic_year_id' => $year->id,
                'source_type' => 'manual',
                'generation_key' => $bill['key'],
                'title' => 'Tagihan Ringkasan',
                'issue_date' => $bill['issue_date'] ?? '2026-07-01',
                'due_date' => $bill['due_date'] ?? '2026-07-10',
                'original_amount' => $bill['total'],
                'discount_amount' => 0,
                'total_amount' => $bill['total'],
                'paid_amount' => $bill['paid'],
                'remaining_amount' => $bill['remaining'],
                'status' => $bill['status'],
            ]);
        }

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.payments.index', [
                'search' => $student->name,
                'student_id' => $student->id,
            ]))
            ->assertOk()
            ->assertSeeInOrder([
                'TOTAL KEWAJIBAN',
                '750.000',
                'TOTAL YANG SUDAH DIBAYARKAN',
                '375.000',
                'SISA TAGIHAN',
                '375.000',
            ])
            ->assertSeeText('2 tagihan tercatat')
            ->assertSeeText('50% dari total kewajiban')
            ->assertDontSeeText('800.000');
    }

    public function test_payment_overview_filters_by_an_accessible_education_unit(): void
    {
        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $assignedUnit = EducationUnit::create(['code' => 'RA', 'name' => 'RA Mambaul Hikmah', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Mambaul Hikmah', 'is_active' => true]);
        $assignedClass = SchoolClass::create(['education_unit_id' => $assignedUnit->id, 'name' => 'A1', 'level' => 'A1', 'is_active' => true]);
        $otherAssignedClass = SchoolClass::create(['education_unit_id' => $assignedUnit->id, 'name' => 'B1', 'level' => 'B1', 'is_active' => true]);
        $otherClass = SchoolClass::create(['education_unit_id' => $otherUnit->id, 'name' => 'I A', 'level' => 'Kelas I', 'is_active' => true]);
        Student::create([
            'nis' => 'UNIT-RA-01',
            'name' => 'Siswa Unit RA',
            'gender' => 'P',
            'school_class_id' => $assignedClass->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        Student::create([
            'nis' => 'UNIT-RA-02',
            'name' => 'Siswa Kelas Lain',
            'gender' => 'P',
            'school_class_id' => $otherAssignedClass->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        Student::create([
            'nis' => 'UNIT-MI-01',
            'name' => 'Siswa Unit MI',
            'gender' => 'L',
            'school_class_id' => $otherClass->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);

        $cashier = $this->scopedCashier($assignedUnit);

        $this->actingAs($cashier)
            ->get(route('finance.payments.index'))
            ->assertOk()
            ->assertSee('name="class_id" aria-label="Filter kelas" data-payment-class-filter disabled', false)
            ->assertSeeText('Pilih unit pendidikan terlebih dahulu');

        $this->actingAs($cashier)
            ->get(route('finance.payments.index', ['unit_id' => $assignedUnit->id]))
            ->assertOk()
            ->assertSeeText('Siswa Unit RA')
            ->assertDontSeeText('Siswa Unit MI')
            ->assertSee('<option value="'.$assignedUnit->id.'" selected>', false)
            ->assertDontSee('Filter Unit:', false)
            ->assertDontSee('<option value="'.$otherUnit->id.'">', false);

        $this->actingAs($cashier)
            ->get(route('finance.payments.index', ['unit_id' => $assignedUnit->id, 'class_id' => $assignedClass->id]))
            ->assertOk()
            ->assertSeeText('Siswa Unit RA')
            ->assertDontSeeText('Siswa Kelas Lain')
            ->assertSee('data-payment-class-unit="'.$assignedUnit->id.'"', false)
            ->assertSeeText('RA - A1');

        $this->actingAs($cashier)
            ->get(route('finance.payments.index', ['class_id' => $assignedClass->id]))
            ->assertUnprocessable();

        $this->actingAs($cashier)
            ->get(route('finance.payments.index', ['unit_id' => $otherUnit->id]))
            ->assertForbidden();
    }

    public function test_payment_overview_uses_bill_due_dates_for_statuses_and_compact_columns(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-07-10 09:00:00'));

        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'RA', 'name' => 'RA Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'A1', 'level' => 'A1', 'is_active' => true]);
        $students = collect([
            Student::create(['nis' => 'STATUS-LUNAS', 'name' => 'Siswa Lunas', 'gender' => 'P', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true]),
            Student::create(['nis' => 'STATUS-BERJALAN', 'name' => 'Siswa Berjalan', 'gender' => 'P', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true]),
            Student::create(['nis' => 'STATUS-JATUH-TEMPO', 'name' => 'Siswa Jatuh Tempo', 'gender' => 'P', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true]),
        ]);

        $createBill = function (Student $student, string $dueDate, int $paidAmount, int $remainingAmount): void {
            Bill::create([
                'student_id' => $student->id,
                'academic_year_id' => $student->academic_year_id,
                'source_type' => 'spp',
                'generation_key' => hash('sha256', 'payment-overview-status-'.$student->id),
                'year' => 2026,
                'month' => 7,
                'title' => 'SPP Juli 2026',
                'issue_date' => '2026-07-01',
                'due_date' => $dueDate,
                'original_amount' => 100000,
                'discount_amount' => 0,
                'total_amount' => 100000,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'status' => $remainingAmount === 0 ? 'Lunas' : 'Belum Dibayar',
            ]);
        };

        $createBill($students[0], '2026-07-10', 100000, 0);
        $createBill($students[1], '2026-07-10', 0, 100000);
        $createBill($students[2], '2026-07-09', 0, 100000);

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.payments.index', ['unit_id' => $unit->id, 'class_id' => $class->id]))
            ->assertOk()
            ->assertSee('<th>No</th>', false)
            ->assertSee('<th>Nama Siswa</th>', false)
            ->assertSee('<th>Kelas</th>', false)
            ->assertSee('<th>Total Tagihan</th>', false)
            ->assertDontSee('<th>Telah Dibayar</th>', false)
            ->assertDontSee('<th>Sisa Tagihan</th>', false)
            ->assertDontSee('NIS:', false)
            ->assertSeeText('STATUS-LUNAS/RA')
            ->assertSeeText('Lunas')
            ->assertSeeText('Berjalan')
            ->assertSeeText('Jatuh Tempo');

        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $classCell = (new \DOMXPath($dom))->query('//table[contains(concat(" ", normalize-space(@class), " "), " payment-overview-table ")]/tbody/tr[1]/td[3]')->item(0);
        $this->assertNotNull($classCell);
        $this->assertSame('A1', trim($classCell->textContent));
    }

    public function test_payment_overview_paginates_ten_students_per_page(): void
    {
        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '1A', 'level' => 1, 'is_active' => true]);

        foreach (range(1, 11) as $number) {
            Student::create([
                'nis' => 'PAGE-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'name' => 'Siswa Halaman '.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'gender' => 'P',
                'school_class_id' => $class->id,
                'academic_year_id' => $year->id,
                'is_active' => true,
            ]);
        }

        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->get(route('finance.payments.index', ['unit_id' => $unit->id]))
            ->assertOk()
            ->assertSeeText('Siswa Halaman 01')
            ->assertSeeText('Siswa Halaman 10')
            ->assertDontSeeText('Siswa Halaman 11')
            ->assertSeeText('Lunas')
            ->assertSeeText('Menampilkan 1-10 dari 11 siswa')
            ->assertSee('page=2', false);

        $this->actingAs($user)
            ->get(route('finance.payments.index', ['unit_id' => $unit->id, 'page' => 2]))
            ->assertOk()
            ->assertDontSeeText('Siswa Halaman 01')
            ->assertSeeText('Siswa Halaman 11')
            ->assertSeeText('Menampilkan 11-11 dari 11 siswa');
    }

    public function test_payment_overview_supports_approved_page_sizes_and_preserves_filters(): void
    {
        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '1A', 'level' => 1, 'is_active' => true]);

        foreach (range(1, 101) as $number) {
            Student::create([
                'nis' => 'SHOW-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'name' => 'Siswa Ukuran Halaman '.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'gender' => 'P',
                'school_class_id' => $class->id,
                'academic_year_id' => $year->id,
                'is_active' => true,
            ]);
        }

        $user = User::factory()->create(['role' => 'admin']);
        $filters = [
            'search' => 'Siswa Ukuran Halaman',
            'unit_id' => $unit->id,
            'class_id' => $class->id,
        ];

        $defaultResponse = $this->actingAs($user)
            ->get(route('finance.payments.index', $filters))
            ->assertOk()
            ->assertSeeText('Menampilkan 1-10 dari 101 siswa')
            ->assertSee('<option value="10" selected>', false);

        $this->assertStringContainsString('page=2', $defaultResponse->getContent());
        $toolbarDom = new \DOMDocument;
        @$toolbarDom->loadHTML($defaultResponse->getContent());
        $toolbarXpath = new \DOMXPath($toolbarDom);
        $overviewForm = '//form[contains(concat(" ", normalize-space(@class), " "), " payment-overview-form ")]';
        $filterCard = $overviewForm.'/div[contains(concat(" ", normalize-space(@class), " "), " payment-overview-filter ")]';
        $tableToolbar = $overviewForm.'/div[contains(concat(" ", normalize-space(@class), " "), " payment-overview-table-toolbar ")]';
        $this->assertCount(1, $toolbarXpath->query($overviewForm));
        $this->assertCount(1, $toolbarXpath->query($tableToolbar.'//select[@name="per_page"]'));
        $this->assertCount(1, $toolbarXpath->query($tableToolbar.'//select[@data-payment-overview-per-page]'));
        $this->assertCount(1, $toolbarXpath->query($tableToolbar.'//input[@name="search"]'));
        $this->assertCount(0, $toolbarXpath->query($filterCard.'//select[@name="per_page"]'));
        $this->assertCount(0, $toolbarXpath->query($filterCard.'//input[@name="search"]'));

        $response = $this->actingAs($user)
            ->get(route('finance.payments.index', $filters + ['per_page' => 25]))
            ->assertOk()
            ->assertSeeText('Menampilkan 1-25 dari 101 siswa')
            ->assertSee('<option value="25" selected>', false);
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $pageTwoUrl = (new \DOMXPath($dom))->query('//a[@aria-label="Halaman 2"]/@href')->item(0)?->nodeValue;
        $this->assertNotNull($pageTwoUrl);
        parse_str(parse_url(html_entity_decode($pageTwoUrl), PHP_URL_QUERY) ?? '', $pageTwoQuery);
        $this->assertSame('Siswa Ukuran Halaman', $pageTwoQuery['search'] ?? null);
        $this->assertSame((string) $unit->id, $pageTwoQuery['unit_id'] ?? null);
        $this->assertSame((string) $class->id, $pageTwoQuery['class_id'] ?? null);
        $this->assertSame('25', $pageTwoQuery['per_page'] ?? null);
        $this->assertSame('2', $pageTwoQuery['page'] ?? null);

        $this->actingAs($user)
            ->get(route('finance.payments.index', $filters + ['per_page' => 50]))
            ->assertOk()
            ->assertSeeText('Menampilkan 1-50 dari 101 siswa')
            ->assertSee('<option value="50" selected>', false);

        $this->actingAs($user)
            ->get(route('finance.payments.index', $filters + ['per_page' => 100]))
            ->assertOk()
            ->assertSeeText('Menampilkan 1-100 dari 101 siswa')
            ->assertSee('<option value="100" selected>', false);

        $this->actingAs($user)
            ->get(route('finance.payments.index', $filters + ['per_page' => 'all', 'page' => 2]))
            ->assertOk()
            ->assertSeeText('Menampilkan 1-101 dari 101 siswa')
            ->assertSee('<option value="all" selected>', false)
            ->assertDontSee('aria-label="Halaman berikutnya"', false);

        $this->actingAs($user)
            ->get(route('finance.payments.index', $filters + ['per_page' => 99]))
            ->assertOk()
            ->assertSeeText('Menampilkan 1-10 dari 101 siswa')
            ->assertSee('<option value="10" selected>', false);
    }

    public function test_bill_list_preserves_hierarchy_default_selection_and_single_visible_total(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-07-05 10:00:00'));

        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '7A', 'level' => 7]);
        $student = Student::create([
            'nis' => 'BILL-LIST',
            'name' => 'Siswa Daftar Tagihan',
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
            'code' => 'SPP-BILL-LIST',
            'name' => 'SPP 7A',
            'amount' => 300000,
            'period' => 'Bulanan',
            'creates_bill' => true,
            'is_active' => true,
        ]);
        FeeType::create([
            'education_unit_id' => $unit->id,
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'daftar-ulang',
            'code' => 'DAFTAR-ULANG-BILL-LIST',
            'name' => 'Daftar Ulang 7A',
            'amount' => 500000,
            'period' => 'Tahunan',
            'creates_bill' => true,
            'is_active' => true,
        ]);
        FeeType::create([
            'education_unit_id' => $unit->id,
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'laundry',
            'code' => 'LAUNDRY-BILL-LIST',
            'name' => 'Laundry 7A',
            'amount' => 100000,
            'period' => 'Bulanan',
            'creates_bill' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.payments.index', [
                'search' => $student->name,
                'student_id' => $student->id,
            ]))
            ->assertOk()
            ->assertDontSeeText('Tagihan Wajib')
            ->assertSeeText('Pembayaran Opsional')
            ->assertSeeText('Total Pembayaran')
            ->assertDontSeeText('Total Dibayar')
            ->assertSee('<b data-payment-total>0,-</b>', false)
            ->assertDontSee('data-payment-mandatory-total', false);

        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $billCard = '//section[contains(concat(" ", normalize-space(@class), " "), " payment-prd-bill-card ")]';
        $mandatorySection = $billCard.'/div[contains(concat(" ", normalize-space(@class), " "), " payment-prd-bill-section ") and not(contains(concat(" ", normalize-space(@class), " "), " payment-one-stop-optional-section "))]';
        $optionalSection = $billCard.'/div[contains(concat(" ", normalize-space(@class), " "), " payment-one-stop-optional-section ")]';
        $billRows = '//div[contains(concat(" ", normalize-space(@class), " "), " payment-prd-bill-row ")]';
        $summaryCard = '//section[contains(concat(" ", normalize-space(@class), " "), " payment-prd-summary-card ")]';

        $this->assertCount(2, $xpath->query($mandatorySection.$billRows));
        $this->assertCount(2, $xpath->query($mandatorySection.$billRows.'/input[@data-payment-bill and @checked]'));
        $this->assertCount(1, $xpath->query($optionalSection.$billRows));
        $this->assertCount(0, $xpath->query($optionalSection.$billRows.'/input[@data-payment-bill and @checked]'));
        $this->assertCount(0, $xpath->query($billCard.'//label[contains(concat(" ", normalize-space(@class), " "), " payment-prd-bill-row ")]'));
        $this->assertCount(3, $xpath->query($billCard.$billRows.'/label[contains(concat(" ", normalize-space(@class), " "), " payment-prd-bill-choice ")]'));
        $this->assertCount(3, $xpath->query($billCard.$billRows.'//*[@data-payment-bill-amount]'));
        $this->assertCount(3, $xpath->query($billCard.$billRows.'//input[@data-payment-bill-input]'));
        $this->assertCount(3, $xpath->query($billCard.$billRows.'//button[@data-payment-bill-full]'));
        $this->assertCount(0, $xpath->query($billCard.'//*[@data-payment-period-select]'));
        $this->assertCount(0, $xpath->query($billCard.'//*[@data-payment-mandatory-total]'));
        $this->assertCount(1, $xpath->query($summaryCard.'//*[@data-payment-total]'));
        $this->assertCount(1, $xpath->query($summaryCard.'//*[@data-payment-selected-summary]'));
        $this->assertCount(1, $xpath->query($summaryCard.'//*[@data-payment-selected-count]'));
        $this->assertCount(1, $xpath->query($summaryCard.'//*[@data-payment-selected-list]'));
        $this->assertCount(1, $xpath->query($summaryCard.'//input[@type="date" and @name="transaction_date"]'));
        $this->assertCount(1, $xpath->query($summaryCard.'//input[@type="time" and @name="transaction_time"]'));
        $this->assertCount(1, $xpath->query($summaryCard.'//fieldset[contains(concat(" ", normalize-space(@class), " "), " payment-prd-method-card ")]'));
        $this->assertCount(0, $xpath->query($summaryCard.'//select[@data-payment-type or @name="payment_method"]'));
        $this->assertCount(0, $xpath->query($summaryCard.'//*[@data-payment-type]'));
        $this->assertCount(0, $xpath->query($summaryCard.'//*[@data-payment-type-option]'));
        $this->assertCount(1, $xpath->query($summaryCard.'//input[@type="hidden" and @name="payment_method" and @data-payment-method and @value="Cash"]'));
        $this->assertCount(2, $xpath->query($summaryCard.'//input[@type="radio" and @data-payment-method-option]'));
        $this->assertCount(1, $xpath->query($summaryCard.'//input[@type="radio" and @data-payment-method-option and @value="Cash" and @checked]'));
        $this->assertCount(0, $xpath->query($summaryCard.'//*[contains(concat(" ", normalize-space(@class), " "), " payment-prd-segment-check ")]'));
        $this->assertCount(0, $xpath->query($summaryCard.'//input[@type="radio" and @data-payment-type-option and @checked]/following-sibling::span//*[name()="svg"]'));
        $this->assertCount(0, $xpath->query($summaryCard.'//input[@type="radio" and @data-payment-method-option and @checked]/following-sibling::span//*[name()="svg"]'));
        $this->assertCount(1, $xpath->query($summaryCard.'//input[@type="hidden" and @name="paid_amount" and @data-payment-paid-display and @value="0"]'));
        $this->assertCount(0, $xpath->query($summaryCard.'//*[@data-payment-paid-total]'));
        $this->assertCount(0, $xpath->query($summaryCard.'//*[@data-payment-transfer-panel]'));
        $this->assertCount(0, $xpath->query($summaryCard.'//input[@name="transfer_proof" and @data-payment-transfer-file and @accept=".jpg,.jpeg,.png,.pdf"]'));
        $this->assertCount(1, $xpath->query($summaryCard.'//button[@data-payment-submit]/*[@data-payment-submit-label]'));

        foreach ($xpath->query($billCard.$billRows) as $row) {
            $checkbox = $xpath->query('./input[@data-payment-bill]', $row)->item(0);
            $choice = $xpath->query('./label[contains(concat(" ", normalize-space(@class), " "), " payment-prd-bill-choice ")]', $row)->item(0);
            $this->assertNotNull($checkbox);
            $this->assertNotNull($choice);
            $this->assertSame($checkbox->getAttribute('id'), $choice->getAttribute('for'));
        }

        $cashOnlyResponse = $this->actingAs($this->scopedCashier($unit))
            ->get(route('finance.payments.index', [
                'search' => $student->name,
                'student_id' => $student->id,
            ]))
            ->assertOk();
        $cashOnlyDom = new \DOMDocument;
        @$cashOnlyDom->loadHTML($cashOnlyResponse->getContent());
        $cashOnlyXpath = new \DOMXPath($cashOnlyDom);
        $this->assertCount(1, $cashOnlyXpath->query($summaryCard.'//input[@type="radio" and @data-payment-method-option and @value="Cash" and @checked]'));
        $this->assertCount(0, $cashOnlyXpath->query($summaryCard.'//input[@type="radio" and @data-payment-method-option and @value="Transfer"]'));
    }

    public function test_transfer_payment_requires_transfer_proof(): void
    {
        config(['payments.transfer_proof_upload_enabled' => true]);
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

    public function test_transfer_payment_can_be_recorded_without_a_proof_while_uploads_are_disabled(): void
    {
        config(['payments.transfer_proof_upload_enabled' => false]);
        $this->travelTo(CarbonImmutable::parse('2026-07-15 09:00:00'));
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'I A', 'level' => 'Kelas I']);
        $student = Student::create([
            'nis' => '3003', 'name' => 'Siswa Transfer Tanpa Bukti', 'gender' => 'L',
            'school_class_id' => $class->id, 'academic_year_id' => $year->id,
            'billing_start_date' => '2026-07-01', 'is_active' => true,
        ]);
        FeeType::create([
            'education_unit_id' => $unit->id, 'academic_year_id' => $year->id,
            'payment_group' => 'spp', 'code' => 'SPP-TRANSFER-NO-PROOF', 'name' => 'SPP Transfer Tanpa Bukti',
            'amount' => 100000, 'period' => 'Bulanan', 'creates_bill' => true, 'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('finance.payments.store'), [
                'student_id' => $student->id,
                'search' => $student->name,
                'bill_keys' => [$student->id.':spp'],
                'payment_method' => 'Transfer',
                'paid_amount' => 100000,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('spp_payments', [
            'student_id' => $student->id,
            'payment_method' => 'Transfer',
            'transfer_proof_path' => null,
            'transfer_proof_file_id' => null,
        ]);
    }

    public function test_transfer_payment_rejects_invalid_or_oversized_proof_before_uploading(): void
    {
        config(['payments.transfer_proof_upload_enabled' => true]);
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create([
            'nis' => '3001', 'name' => 'Siswa Validasi Bukti', 'gender' => 'L',
            'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->from(route('finance.payments.index'))
            ->post(route('finance.payments.store'), [
                'student_id' => $student->id,
                'search' => $student->name,
                'payment_method' => 'Transfer',
                'paid_amount' => 100000,
                'transfer_proof' => UploadedFile::fake()->create('proof.exe', 12, 'application/octet-stream'),
            ])
            ->assertRedirect(route('finance.payments.index'))
            ->assertSessionHasErrors('transfer_proof');

        $this->from(route('finance.payments.index'))
            ->post(route('finance.payments.store'), [
                'student_id' => $student->id,
                'search' => $student->name,
                'payment_method' => 'Transfer',
                'paid_amount' => 100000,
                'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 2049, 'application/pdf'),
            ])
            ->assertRedirect(route('finance.payments.index'))
            ->assertSessionHasErrors('transfer_proof');

        $this->assertDatabaseCount('spp_payments', 0);
        $this->assertDatabaseCount('other_payments', 0);
    }

    public function test_transfer_payment_upload_failure_rejects_the_transaction_without_creating_payments(): void
    {
        config(['payments.transfer_proof_upload_enabled' => true]);
        $this->travelTo(CarbonImmutable::parse('2026-07-15 09:00:00'));
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'I A', 'level' => 'Kelas I']);
        $student = Student::create([
            'nis' => '3002', 'name' => 'Siswa Upload Gagal', 'gender' => 'L',
            'school_class_id' => $class->id, 'academic_year_id' => $year->id,
            'billing_start_date' => '2026-07-01', 'is_active' => true,
        ]);
        FeeType::create([
            'education_unit_id' => $unit->id, 'academic_year_id' => $year->id,
            'payment_group' => 'spp', 'code' => 'SPP-DRIVE-FAIL', 'name' => 'SPP Drive Gagal',
            'amount' => 100000, 'period' => 'Bulanan', 'creates_bill' => true, 'is_active' => true,
        ]);
        $drive = Mockery::mock(GoogleDriveStorageService::class);
        $drive->shouldReceive('upload')->once()->andThrow(new GoogleDriveStorageException('Bukti transfer gagal diunggah ke Google Drive. Silakan coba lagi.'));
        $this->app->instance(GoogleDriveStorageService::class, $drive);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->from(route('finance.payments.index', ['search' => $student->name]))
            ->post(route('finance.payments.store'), [
                'student_id' => $student->id,
                'search' => $student->name,
                'bill_keys' => [$student->id.':spp'],
                'payment_method' => 'Transfer',
                'paid_amount' => 100000,
                'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 12, 'application/pdf'),
            ])
            ->assertRedirect(route('finance.payments.index', ['search' => $student->name]))
            ->assertSessionHasErrors([
                'transfer_proof' => 'Bukti transfer gagal diunggah ke Google Drive. Silakan coba lagi.',
            ]);

        $this->assertDatabaseCount('spp_payments', 0);
        $this->assertDatabaseCount('other_payments', 0);
    }

    public function test_payment_success_flash_contains_backend_receipt_context_and_preserves_registration(): void
    {
        CarbonImmutable::setTestNow('2026-07-15 09:00:00');
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'I A', 'level' => 'Kelas I']);
        $student = Student::create([
            'nis' => 'SUCCESS-001', 'name' => 'Siswa Pembayaran Berhasil', 'gender' => 'L',
            'school_class_id' => $class->id, 'academic_year_id' => $year->id,
            'billing_start_date' => '2026-07-01', 'is_active' => true,
        ]);
        FeeType::create([
            'education_unit_id' => $unit->id, 'academic_year_id' => $year->id,
            'payment_group' => 'spp', 'code' => 'SPP-SUCCESS', 'name' => 'SPP Success',
            'amount' => 100000, 'period' => 'Bulanan', 'creates_bill' => true, 'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $response = $this->post(route('finance.payments.store'), [
            'student_id' => $student->id,
            'registration_id' => $student->id,
            'search' => $student->name,
            'bill_keys' => [$student->id.':spp'],
            'payment_month_counts' => [$student->id.'_spp' => 1],
            'payment_method' => 'Cash',
            'paid_amount' => 100000,
        ]);

        $payment = SppPayment::firstOrFail();
        $confirmation = session('payment_confirmation');
        $this->assertSame($student->name, $confirmation['student']['name']);
        $this->assertSame('MI • I A', $confirmation['context']);
        $this->assertSame('Tunai', $confirmation['payment_method']);
        $this->assertSame('Diterima', $confirmation['status']);
        $this->assertSame(100000, $confirmation['paid_amount']);
        $this->assertSame(route('finance.spp.receipt', $payment), $confirmation['payments'][0]['receipt_url']);
        $this->assertMatchesRegularExpression('/^SPP-\d{8}-\d{6}$/', $confirmation['payments'][0]['receipt_number']);

        $response->assertRedirect(route('finance.payments.index', [
            'search' => $student->name,
            'student_id' => $student->id,
            'registration_id' => $student->id,
        ]));

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('data-payment-success-modal', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSeeInOrder([
                'Pembayaran Berhasil',
                'Transaksi pembayaran berhasil disimpan dan tagihan telah diperbarui.',
                'Cetak Struk',
                'Tutup',
            ])
            ->assertSee('payment-success-actions', false)
            ->assertDontSee('payment-success-summary', false)
            ->assertDontSee('payment-success-student', false)
            ->assertDontSee('payment-success-popup-note', false)
            ->assertDontSee('data-download-receipts', false)
            ->assertDontSee('Bayar Lagi')
            ->assertSee(route('finance.spp.receipt', $payment), false)
            ->assertSee(route('finance.spp.receipt.download', $payment), false);
    }

    public function test_payment_form_submits_without_confirmation_modal(): void
    {
        $context = $this->multiUnitPaymentContext();
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.payments.index', [
                'search' => $context['identity']->name,
                'student_id' => $context['identity']->id,
                'registration_id' => $context['pondokRegistration']->id,
            ]))
            ->assertOk()
            ->assertDontSee('data-payment-confirmation-modal', false)
            ->assertDontSee('Konfirmasi Pembayaran')
            ->assertDontSee('Konfirmasi &amp; Bayar', false)
            ->assertSee('data-payment-submit', false)
            ->assertSee('Bayar &amp; Cetak Struk', false);
    }

    public function test_selecting_a_student_shows_their_five_latest_payment_history_items_below_the_form(): void
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
        $latestPayment = null;
        foreach (range(1, 9) as $minute) {
            $latestPayment = OtherPayment::create([
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
        ]);

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.payments.index', [
                'search' => $selectedStudent->name,
            ]));
        $response->assertOk()
            ->assertSeeInOrder([
                'payment-selected-page-head',
                'payment-one-stop-pay-form',
                'payment-one-stop-history-card',
            ], false)
            ->assertSee('Riwayat Terbaru')
            ->assertSee('Transaksi terakhir siswa ini')
            ->assertDontSee('Lihat Semua di Laporan')
            ->assertDontSee('payment-prd-history-link', false)
            ->assertSee('title="Download PDF"', false)
            ->assertSee('>Struk<', false)
            ->assertSee('>PDF<', false)
            ->assertSee('class="payment-one-stop-history-action danger"', false)
            ->assertSee('data-payment-delete-confirm', false)
            ->assertSee('data-payment-delete-cancel', false)
            ->assertSee('Ya, Hapus')
            ->assertDontSee('>Periode<', false)
            ->assertDontSee('name="history_period"', false)
            ->assertDontSee('data-payment-history-period', false)
            ->assertSee('name="return_url" value="'.e($returnUrl).'"', false)
            ->assertDontSee(route('finance.other.receipt', $olderPayment), false)
            ->assertDontSee(route('finance.other.receipt', $otherPayment), false);

        $historyDom = new \DOMDocument;
        @$historyDom->loadHTML($response->getContent());
        $historyXpath = new \DOMXPath($historyDom);
        $historyItems = '//article[contains(concat(" ", normalize-space(@class), " "), " payment-one-stop-history-item ")]';
        $this->assertCount(5, $historyXpath->query($historyItems));
        $this->assertStringContainsString(route('finance.other.receipt', $latestPayment), $response->getContent());
        $this->assertStringContainsString(route('finance.other.receipt.download', $latestPayment), $response->getContent());

        Role::updateOrCreate(['key' => 'kasir-history-viewer'], [
            'name' => 'Kasir History Viewer',
            'permissions' => ['payments.cash.create', 'payments.view_unit'],
            'is_active' => true,
        ]);
        $viewer = User::factory()->create(['role' => 'kasir-history-viewer']);
        $viewer->educationUnits()->attach($unit->id);
        $this->actingAs($viewer)
            ->get(route('finance.payments.index', [
                'search' => $selectedStudent->name,
                'student_id' => $selectedStudent->id,
            ]))
            ->assertOk()
            ->assertDontSee('data-payment-history-delete-form', false);

        $deleteResponse = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->delete(route('finance.other.destroy', $latestPayment), ['return_url' => $returnUrl])
            ->assertRedirect($returnUrl)
            ->assertSessionHas('success', 'Transaksi pembayaran berhasil dihapus.');
        $this->get($deleteResponse->headers->get('Location'))
            ->assertOk()
            ->assertSee('data-payment-delete-success-modal', false)
            ->assertSeeInOrder([
                'Transaksi Dihapus',
                'Transaksi pembayaran berhasil dihapus dan sisa tagihan telah diperbarui.',
                'Tutup',
            ])
            ->assertDontSee('Pembayaran Berhasil');
        $this->assertDatabaseMissing('other_payments', ['id' => $latestPayment->id]);
    }

    public function test_recent_history_empty_state_is_compact_for_selected_student_without_payments(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create([
            'nis' => 'HISTORY-EMPTY', 'name' => 'Siswa Tanpa Riwayat', 'gender' => 'L',
            'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.payments.index', ['search' => $student->name]))
            ->assertOk()
            ->assertSee('Riwayat Terbaru')
            ->assertSee('Transaksi terakhir siswa ini')
            ->assertSee('Belum ada riwayat pembayaran untuk siswa ini.')
            ->assertSee('Transaksi yang berhasil akan muncul di sini.')
            ->assertDontSee('payment-one-stop-history-item', false);
    }

    public function test_recent_history_combines_spp_laundry_and_other_for_the_selected_student(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create([
            'nis' => 'HISTORY-MIXED', 'name' => 'Siswa History Campuran', 'gender' => 'L',
            'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true,
        ]);
        $sppFee = FeeType::create([
            'education_unit_id' => $unit->id, 'school_class_id' => $class->id,
            'academic_year_id' => $year->id, 'payment_group' => 'spp', 'code' => 'SPP-HISTORY',
            'name' => 'SPP MTs', 'amount' => 300000, 'period' => 'Bulanan', 'creates_bill' => true, 'is_active' => true,
        ]);
        $laundryFee = FeeType::create([
            'education_unit_id' => $unit->id, 'school_class_id' => $class->id,
            'academic_year_id' => $year->id, 'payment_group' => 'laundry', 'code' => 'LAUNDRY-HISTORY',
            'name' => 'Laundry Bulanan', 'amount' => 100000, 'period' => 'Bulanan', 'creates_bill' => false, 'is_active' => true,
        ]);
        $sppPayment = SppPayment::create([
            'student_id' => $student->id, 'transaction_at' => '2026-07-05 10:00:00', 'payment_method' => 'Cash', 'status' => 'Diterima',
            'original_amount' => 300000, 'total_amount' => 300000, 'paid_amount' => 300000,
            'remaining_amount' => 0, 'payment_status' => 'Lunas',
        ]);
        $sppPayment->items()->create([
            'student_id' => $student->id, 'year' => 2026, 'month' => 7,
            'original_amount' => 300000, 'total_amount' => 300000, 'paid_amount' => 300000,
            'remaining_amount' => 0, 'payment_status' => 'Lunas',
        ]);
        $laundryPayment = OtherPayment::create([
            'student_id' => $student->id, 'fee_type_id' => $laundryFee->id,
            'transaction_at' => '2026-07-05 10:01:00', 'payment_method' => 'Transfer', 'status' => 'Diterima',
            'original_amount' => 100000, 'total_amount' => 100000, 'paid_amount' => 100000,
            'remaining_amount' => 0, 'payment_status' => 'Lunas',
        ]);
        $laundryPayment->items()->create([
            'student_id' => $student->id, 'fee_type_id' => $laundryFee->id, 'year' => 2026, 'month' => 7,
            'original_amount' => 100000, 'total_amount' => 100000, 'paid_amount' => 100000,
            'remaining_amount' => 0, 'payment_status' => 'Lunas',
        ]);
        $otherFee = FeeType::create([
            'education_unit_id' => $unit->id, 'school_class_id' => $class->id,
            'academic_year_id' => $year->id, 'payment_group' => 'lain-lain', 'code' => 'OTHER-HISTORY',
            'name' => 'Daftar Ulang', 'amount' => 500000, 'period' => 'Sekali Bayar', 'creates_bill' => true, 'is_active' => true,
        ]);
        $otherPayment = OtherPayment::create([
            'student_id' => $student->id, 'fee_type_id' => $otherFee->id,
            'transaction_at' => '2026-07-05 10:02:00', 'payment_method' => 'Cash', 'status' => 'Diterima',
            'original_amount' => 500000, 'total_amount' => 500000, 'paid_amount' => 500000,
            'remaining_amount' => 0, 'payment_status' => 'Lunas',
        ]);
        $otherPayment->items()->create([
            'student_id' => $student->id, 'fee_type_id' => $otherFee->id, 'year' => 2026, 'month' => 7,
            'original_amount' => 500000, 'total_amount' => 500000, 'paid_amount' => 500000,
            'remaining_amount' => 0, 'payment_status' => 'Lunas',
        ]);

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.payments.index', ['search' => $student->name]))
            ->assertOk()
            ->assertSee('BIAYA BULANAN')
            ->assertSee('Laundry Bulanan')
            ->assertSee('Daftar Ulang')
            ->assertSee('Tunai')
            ->assertSee('Transfer')
            ->assertSee(route('finance.spp.receipt', $sppPayment), false)
            ->assertSee(route('finance.spp.receipt.download', $sppPayment), false)
            ->assertSee(route('finance.other.receipt', $laundryPayment), false)
            ->assertSee(route('finance.other.receipt.download', $laundryPayment), false)
            ->assertSee(route('finance.other.receipt', $otherPayment), false)
            ->assertSee(route('finance.other.receipt.download', $otherPayment), false);

        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(3, $xpath->query('//article[contains(concat(" ", normalize-space(@class), " "), " payment-one-stop-history-item ")]'));
        $this->assertCount(3, $xpath->query('//article[contains(concat(" ", normalize-space(@class), " "), " payment-one-stop-history-item ")]//div[contains(concat(" ", normalize-space(@class), " "), " payment-one-stop-history-side ")]'));
    }

    public function test_multi_unit_student_switches_registration_context_and_resets_bill_list(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-20 10:00:00'));
        $context = $this->multiUnitPaymentContext();
        $admin = User::factory()->create(['role' => 'admin']);

        $defaultResponse = $this->actingAs($admin)->get(route('finance.payments.index', [
            'search' => $context['identity']->name,
        ]));

        $defaultResponse->assertOk()
            ->assertSee('Unit Aktif')
            ->assertDontSee('Ganti Unit')
            ->assertSee('payment-unit-context-form', false)
            ->assertSee('payment-context-segmented', false)
            ->assertDontSee('payment-context-check', false)
            ->assertSee('name="registration_id"', false)
            ->assertSee('data-payment-context-switch', false)
            ->assertSee('value="'.$context['identity']->id.'"', false)
            ->assertSee('value="'.$context['pondokRegistration']->id.'"', false)
            ->assertSee($context['identity']->nis)
            ->assertDontSee('NIS '.$context['identity']->nis)
            ->assertSee('BIAYA BULANAN');
        $defaultDom = new \DOMDocument;
        @$defaultDom->loadHTML($defaultResponse->getContent());
        $defaultMeta = (new \DOMXPath($defaultDom))->query('//p[contains(concat(" ", normalize-space(@class), " "), " payment-selected-student-meta ")]')->item(0);
        $this->assertNotNull($defaultMeta);
        $this->assertSame($context['identity']->nis.' • MTs • VII A', trim(preg_replace('/\s+/', ' ', $defaultMeta->textContent)));
        $this->assertStringContainsString('value="'.$context['identity']->id.':spp"', $defaultResponse->getContent());

        $switchedResponse = $this->actingAs($admin)->get(route('finance.payments.index', [
            'search' => $context['identity']->name,
            'student_id' => $context['identity']->id,
            'registration_id' => $context['pondokRegistration']->id,
        ]));

        $switchedResponse->assertOk()
            ->assertSee($context['pondokRegistration']->nis)
            ->assertDontSee('NIS '.$context['pondokRegistration']->nis)
            ->assertSee('PONPES')
            ->assertSee('BIAYA BULANAN')
            ->assertSee('Unit Aktif')
            ->assertSee('payment-context-segmented', false)
            ->assertDontSee('Ganti Unit');
        $switchedDom = new \DOMDocument;
        @$switchedDom->loadHTML($switchedResponse->getContent());
        $switchedMeta = (new \DOMXPath($switchedDom))->query('//p[contains(concat(" ", normalize-space(@class), " "), " payment-selected-student-meta ")]')->item(0);
        $this->assertNotNull($switchedMeta);
        $this->assertSame($context['pondokRegistration']->nis.' • PONPES • Asrama A', trim(preg_replace('/\s+/', ' ', $switchedMeta->textContent)));
        $this->assertStringContainsString('value="'.$context['pondokRegistration']->id.':spp"', $switchedResponse->getContent());
        $this->assertStringNotContainsString('value="'.$context['identity']->id.':spp"', $switchedResponse->getContent());

        $this->actingAs($admin)
            ->get(route('finance.payments.index'))
            ->assertOk()
            ->assertSee('Cari Siswa')
            ->assertDontSee('Ganti Unit');

        $reverseResponse = $this->actingAs($admin)->get(route('finance.payments.index', [
            'search' => $context['identity']->name,
            'student_id' => $context['identity']->id,
            'registration_id' => $context['identity']->id,
        ]));

        $reverseResponse->assertOk()
            ->assertSee($context['identity']->nis)
            ->assertDontSee('NIS '.$context['identity']->nis)
            ->assertSee('BIAYA BULANAN')
            ->assertSee('payment-context-segmented', false)
            ->assertSee('Unit Aktif');
        $this->assertStringContainsString('value="'.$context['identity']->id.':spp"', $reverseResponse->getContent());
        $this->assertStringNotContainsString('value="'.$context['pondokRegistration']->id.':spp"', $reverseResponse->getContent());
    }

    public function test_multi_unit_student_uses_a_select_for_more_than_three_active_registrations(): void
    {
        $context = $this->multiUnitPaymentContext();
        $miUnit = EducationUnit::create(['code' => 'MI', 'name' => 'Madrasah Ibtidaiyah', 'is_active' => true]);
        $maUnit = EducationUnit::create(['code' => 'MA', 'name' => 'Madrasah Aliyah', 'is_active' => true]);
        $miClass = SchoolClass::create(['education_unit_id' => $miUnit->id, 'name' => 'VI A', 'level' => 'Kelas VI', 'is_active' => true]);
        $maClass = SchoolClass::create(['education_unit_id' => $maUnit->id, 'name' => 'X A', 'level' => 'Kelas X', 'is_active' => true]);
        $miRegistration = Student::create([
            'identity_student_id' => $context['identity']->id,
            'nis' => 'MULTI-MI',
            'name' => $context['identity']->name,
            'gender' => 'L',
            'school_class_id' => $miClass->id,
            'academic_year_id' => $context['year']->id,
            'billing_start_date' => '2026-07-01',
            'is_active' => true,
        ]);
        $maRegistration = Student::create([
            'identity_student_id' => $context['identity']->id,
            'nis' => 'MULTI-MA',
            'name' => $context['identity']->name,
            'gender' => 'L',
            'school_class_id' => $maClass->id,
            'academic_year_id' => $context['year']->id,
            'billing_start_date' => '2026-07-01',
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.payments.index', ['search' => $context['identity']->name]))
            ->assertOk()
            ->assertSee('Unit Aktif')
            ->assertSee('id="payment-unit-context-select"', false)
            ->assertSee('name="registration_id"', false)
            ->assertSee('MI • VI A')
            ->assertSee('MA • X A')
            ->assertSee('value="'.$miRegistration->id.'"', false)
            ->assertSee('value="'.$maRegistration->id.'"', false)
            ->assertDontSee('payment-context-segmented', false);
    }

    public function test_single_unit_student_does_not_show_unit_switcher(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create([
            'nis' => 'SINGLE-UNIT', 'name' => 'Siswa Satu Unit', 'gender' => 'L',
            'school_class_id' => $class->id, 'academic_year_id' => $year->id,
            'billing_start_date' => '2026-07-01', 'is_active' => true,
        ]);
        FeeType::create([
            'education_unit_id' => $unit->id, 'academic_year_id' => $year->id,
            'payment_group' => 'spp', 'code' => 'SPP-SINGLE', 'name' => 'SPP MTs',
            'amount' => 100000, 'period' => 'Bulanan', 'creates_bill' => true, 'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.payments.index', ['search' => $student->name]))
            ->assertOk()
            ->assertSee($student->nis)
            ->assertSee('MTs')
            ->assertSee('VII A')
            ->assertDontSee('Unit Aktif')
            ->assertDontSee('Ganti Unit')
            ->assertDontSee('payment-unit-context-form', false)
            ->assertDontSee('payment-context-segmented', false);
    }

    public function test_single_ponpes_student_does_not_show_unit_switcher(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'Asrama', 'level' => 'Asrama']);
        $student = Student::create([
            'nis' => 'SINGLE-PONPES', 'name' => 'Santri Satu Unit', 'gender' => 'L',
            'school_class_id' => $class->id, 'academic_year_id' => $year->id,
            'billing_start_date' => '2026-07-01', 'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.payments.index', ['search' => $student->name]))
            ->assertOk()
            ->assertSee($student->nis)
            ->assertSee('PONPES')
            ->assertSee('Asrama')
            ->assertDontSee('Unit Aktif')
            ->assertDontSee('payment-unit-context-form', false)
            ->assertDontSee('payment-context-segmented', false);
    }

    public function test_inactive_linked_registration_is_not_offered_as_a_payment_context(): void
    {
        $context = $this->multiUnitPaymentContext();
        $context['pondokRegistration']->update(['is_active' => false]);

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.payments.index', ['search' => $context['identity']->name]));

        $response->assertOk()
            ->assertSee('MTs')
            ->assertSee('VII A')
            ->assertDontSee('Unit Aktif')
            ->assertDontSee('payment-unit-context-form', false)
            ->assertDontSee('registration_id='.$context['pondokRegistration']->id, false);
    }

    public function test_multi_unit_context_supports_ma_and_ponpes_labels(): void
    {
        $context = $this->multiUnitPaymentContext();
        $context['mtsUnit']->update(['code' => 'MA']);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.payments.index', ['search' => $context['identity']->name]))
            ->assertOk()
            ->assertSee('MA • VII A')
            ->assertSee('PONPES • Asrama A')
            ->assertSee('Unit Aktif');
    }

    public function test_long_student_name_keeps_student_context_copy_and_actions_visible(): void
    {
        $context = $this->multiUnitPaymentContext();
        $longName = 'Mukhammad Ibrohim Rizqi Dermawan Siswa Multi Unit';
        $context['identity']->update(['name' => $longName]);
        $context['pondokRegistration']->update(['name' => $longName]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.payments.index', ['search' => $longName]))
            ->assertOk()
            ->assertSee($longName)
            ->assertSee('Unit Aktif')
            ->assertDontSee('Ganti Siswa');
    }

    public function test_multi_unit_switcher_is_limited_to_units_accessible_by_the_operator(): void
    {
        $context = $this->multiUnitPaymentContext();
        $cashier = $this->scopedCashier($context['mtsUnit']);

        $this->actingAs($cashier)
            ->get(route('finance.payments.index', ['search' => $context['identity']->name]))
            ->assertOk()
            ->assertSee('BIAYA BULANAN')
            ->assertDontSee('Unit Aktif')
            ->assertDontSee('Ganti Unit');
    }

    public function test_scoped_operator_can_open_accessible_registration_when_identity_row_is_in_another_unit(): void
    {
        $context = $this->multiUnitPaymentContext();
        $cashier = $this->scopedCashier($context['pondokUnit']);

        $this->actingAs($cashier)
            ->get(route('finance.payments.index', [
                'student_id' => $context['identity']->id,
                'registration_id' => $context['pondokRegistration']->id,
            ]))
            ->assertOk()
            ->assertSee($context['pondokRegistration']->nis)
            ->assertDontSee('NIS '.$context['pondokRegistration']->nis)
            ->assertSee('PONPES')
            ->assertSee('Asrama A')
            ->assertDontSee('Ganti Unit');
    }

    public function test_registration_context_cannot_be_taken_from_another_student_identity(): void
    {
        $context = $this->multiUnitPaymentContext();
        $otherStudent = Student::create([
            'nis' => 'OTHER-IDENTITY', 'name' => 'Identitas Lain', 'gender' => 'P',
            'school_class_id' => $context['pondokClass']->id,
            'academic_year_id' => $context['year']->id, 'billing_start_date' => '2026-07-01', 'is_active' => true,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('finance.payments.index', [
                'search' => $context['identity']->name,
                'student_id' => $context['identity']->id,
                'registration_id' => $otherStudent->id,
            ]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('finance.payments.store'), [
                'student_id' => $context['identity']->id,
                'registration_id' => $otherStudent->id,
                'search' => $context['identity']->name,
                'bill_keys' => [$otherStudent->id.':spp'],
                'payment_method' => 'Cash',
                'paid_amount' => 100000,
            ])
            ->assertForbidden();
    }

    public function test_recent_history_follows_the_active_registration_context(): void
    {
        $context = $this->multiUnitPaymentContext();
        $payment = SppPayment::create([
            'student_id' => $context['pondokRegistration']->id,
            'transaction_at' => '2026-09-20 09:00:00', 'payment_method' => 'Cash', 'status' => 'Diterima',
            'original_amount' => 200000, 'total_amount' => 200000, 'paid_amount' => 200000,
            'remaining_amount' => 0, 'payment_status' => 'Lunas',
        ]);
        $payment->items()->create([
            'student_id' => $context['pondokRegistration']->id, 'year' => 2026, 'month' => 9,
            'original_amount' => 200000, 'total_amount' => 200000, 'paid_amount' => 200000,
            'remaining_amount' => 0, 'payment_status' => 'Lunas',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('finance.payments.index', [
                'search' => $context['identity']->name,
                'student_id' => $context['identity']->id,
                'registration_id' => $context['identity']->id,
            ]))
            ->assertOk()
            ->assertDontSee('SPP PONPES')
            ->assertDontSee(route('finance.spp.receipt', $payment), false);

        $this->actingAs($admin)
            ->get(route('finance.payments.index', [
                'search' => $context['identity']->name,
                'student_id' => $context['identity']->id,
                'registration_id' => $context['pondokRegistration']->id,
            ]))
            ->assertOk()
            ->assertSee('BIAYA BULANAN')
            ->assertSee(route('finance.spp.receipt', $payment), false);
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
            ]))
            ->assertOk()
            ->assertSee('Pembayaran Opsional')
            ->assertDontSee('1 Tagihan')
            ->assertSee('payment-one-stop-optional-section is-only-optional', false)
            ->assertSee('1 Pilihan')
            ->assertSee('name="optional_keys[]"', false)
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
            ->assertSee('Ahmad Fauzan')
            ->assertDontSee('2 unit')
            ->assertSee('MTS-001')
            ->assertDontSee('NIS PP-099')
            ->assertSee(route('finance.spp.create', ['student_id' => $identity->id]), false)
            ->assertSee('payment-context-segmented', false)
            ->assertSee('name="registration_id"', false)
            ->assertSee('value="'.$boarding->id.'"', false)
            ->assertDontSee(route('finance.other.create', ['category' => 'laundry', 'student_id' => $boarding->id]));
    }

    public function test_central_payment_import_page_uses_existing_importers(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/keuangan/pembayaran/import')
            ->assertOk()
            ->assertSee('Import Pembayaran')
            ->assertSee('Unggah file Excel, tentukan konteks pembayaran, lalu validasi data sebelum diimpor.')
            ->assertDontSee('payment-import-workflow', false)
            ->assertSee('File Excel')
            ->assertSee(route('finance.spp.import.preview'), false)
            ->assertSee(route('finance.other.import.preview', ['category' => 'daftar-ulang']), false)
            ->assertSee(route('finance.other.import.preview', ['category' => 'laundry']), false)
            ->assertSee('Jenis Pembayaran')
            ->assertSee('Unit Pendidikan')
            ->assertSee('Bulan')
            ->assertSee('Tahun')
            ->assertSee('data-payment-import', false)
            ->assertSee('data-payment-import-dropzone', false)
            ->assertSee('data-payment-import-file-selected', false)
            ->assertSee('data-payment-import-file-change', false)
            ->assertSee('data-payment-import-file-remove', false)
            ->assertSee('Preview &amp; Validasi', false)
            ->assertDontSee('Tidak ada file yang dipilih');
    }

    public function test_payment_import_setup_preserves_spp_unit_month_and_year_context_options(): void
    {
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Ponpes Mambaul Hikmah', 'is_active' => true]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.payments.import'))
            ->assertOk()
            ->assertSee('name="unit_id"', false)
            ->assertSee('value="'.$unit->id.'"', false)
            ->assertSee('name="month"', false)
            ->assertSee('value="7"', false)
            ->assertSee('Juli')
            ->assertSee('name="year"', false)
            ->assertSee('data-payment-import-spp-field', false);
    }

    public function test_spp_import_preview_requires_a_file(): void
    {
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Ponpes Mambaul Hikmah', 'is_active' => true]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('finance.spp.import.preview'), [
                'unit_id' => $unit->id,
                'month' => 7,
                'year' => 2025,
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_spp_import_preview_rejects_invalid_file_type(): void
    {
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Ponpes Mambaul Hikmah', 'is_active' => true]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('finance.spp.import.preview'), [
                'unit_id' => $unit->id,
                'month' => 7,
                'year' => 2025,
                'file' => UploadedFile::fake()->create('pembayaran.csv', 10, 'text/csv'),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_spp_import_preview_rejects_files_over_ten_megabytes(): void
    {
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Ponpes Mambaul Hikmah', 'is_active' => true]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('finance.spp.import.preview'), [
                'unit_id' => $unit->id,
                'month' => 7,
                'year' => 2025,
                'file' => UploadedFile::fake()->create('pembayaran.xlsx', 10241, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            ])
            ->assertSessionHasErrors('file');
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
            ->assertDontSee('Bayar sampai')
            ->assertSee('NOMINAL PEMBAYARAN')
            ->assertSee('data-payment-bill-full', false)
            ->assertSee('Total Pembayaran')
            ->assertSee('Metode Pembayaran')
            ->assertSee('Transfer')
            ->assertDontSee('@hidden')
            ->assertDontSee('payment-transfer-copy-button', false)
            ->assertDontSee('aria-label="Salin rekening"', false)
            ->assertSee('class="button button-primary payment-one-stop-pay-button"', false)
            ->assertDontSee('payment-prd-history-link', false)
            ->assertSee('Bayar &amp; Cetak Struk', false)
            ->assertSee('Juli 2026')
            ->assertDontSeeText('Desember 2026');

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

    public function test_payment_bill_cards_expose_per_card_input_and_full_control_without_period_selector(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-07-05 10:00:00'));
        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '1A', 'level' => 1]);
        $student = Student::create([
            'nis' => 'CARD-001', 'name' => 'Siswa Kartu Tagihan', 'gender' => 'L',
            'school_class_id' => $class->id, 'academic_year_id' => $year->id,
            'billing_start_date' => '2026-07-01', 'is_active' => true,
        ]);
        FeeType::create([
            'education_unit_id' => $unit->id, 'academic_year_id' => $year->id,
            'payment_group' => 'spp', 'code' => 'SPP-CARD', 'name' => 'SPP Kartu',
            'amount' => 100000, 'period' => 'Bulanan', 'creates_bill' => true, 'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.payments.index', ['search' => $student->name]))
            ->assertOk()
            ->assertDontSee('SIAP DIBAYAR')
            ->assertSee('BIAYA BULANAN')
            ->assertDontSee('payment-prd-bill-selection', false)
            ->assertSee('NOMINAL PEMBAYARAN')
            ->assertSee('data-payment-bill-input', false)
            ->assertSee('data-payment-bill-full', false)
            ->assertSee('name="allocation_amounts['.$student->id.':spp]"', false)
            ->assertDontSee('Bayar sampai')
            ->assertDontSee('data-payment-bill-locked', false);
    }

    public function test_transaction_hub_records_each_card_allocation_and_uses_their_sum_as_the_cashier_total(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-07-05 10:00:00'));
        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '1A', 'level' => 1]);
        $student = Student::create([
            'nis' => 'ALLOC-001', 'name' => 'Siswa Alokasi Kartu', 'gender' => 'L',
            'school_class_id' => $class->id, 'academic_year_id' => $year->id,
            'billing_start_date' => '2026-07-01', 'is_active' => true,
        ]);
        FeeType::create([
            'education_unit_id' => $unit->id, 'academic_year_id' => $year->id,
            'payment_group' => 'spp', 'code' => 'SPP-ALLOC', 'name' => 'SPP Alokasi',
            'amount' => 100000, 'period' => 'Bulanan', 'creates_bill' => true, 'is_active' => true,
        ]);
        $registrationFee = FeeType::create([
            'education_unit_id' => $unit->id, 'academic_year_id' => $year->id,
            'payment_group' => 'daftar-ulang', 'code' => 'DU-ALLOC', 'name' => 'Daftar Ulang Alokasi',
            'amount' => 50000, 'period' => 'Sekali Bayar', 'creates_bill' => true, 'is_active' => true,
        ]);

        $sppKey = $student->id.':spp';
        $registrationKey = $student->id.':daftar-ulang';
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('finance.payments.store'), [
                'student_id' => $student->id,
                'search' => $student->name,
                'bill_keys' => [$sppKey, $registrationKey],
                'payment_month_counts' => [$student->id.'_spp' => 1],
                'allocation_amounts' => [$sppKey => 25000, $registrationKey => 30000],
                'transaction_date' => '2026-07-06',
                'transaction_time' => '13:45',
                'payment_method' => 'Cash',
                'paid_amount' => 55000,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('spp_payments', [
            'student_id' => $student->id,
            'paid_amount' => 25000,
            'transaction_at' => '2026-07-06 13:45:00',
        ]);
        $this->assertDatabaseHas('other_payments', [
            'student_id' => $student->id,
            'fee_type_id' => $registrationFee->id,
            'paid_amount' => 30000,
            'transaction_at' => '2026-07-06 13:45:00',
        ]);
    }

    public function test_transaction_hub_rejects_a_card_allocation_that_exceeds_its_remaining_amount(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-07-05 10:00:00'));
        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '1A', 'level' => 1]);
        $student = Student::create([
            'nis' => 'ALLOC-OVER', 'name' => 'Siswa Alokasi Lebih', 'gender' => 'L',
            'school_class_id' => $class->id, 'academic_year_id' => $year->id,
            'billing_start_date' => '2026-07-01', 'is_active' => true,
        ]);
        FeeType::create([
            'education_unit_id' => $unit->id, 'academic_year_id' => $year->id,
            'payment_group' => 'spp', 'code' => 'SPP-OVER', 'name' => 'SPP Batas',
            'amount' => 100000, 'period' => 'Bulanan', 'creates_bill' => true, 'is_active' => true,
        ]);
        FeeType::create([
            'education_unit_id' => $unit->id, 'academic_year_id' => $year->id,
            'payment_group' => 'daftar-ulang', 'code' => 'DU-OVER', 'name' => 'Daftar Ulang Batas',
            'amount' => 50000, 'period' => 'Sekali Bayar', 'creates_bill' => true, 'is_active' => true,
        ]);

        $sppKey = $student->id.':spp';
        $registrationKey = $student->id.':daftar-ulang';
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->from(route('finance.payments.index', ['search' => $student->name]))
            ->post(route('finance.payments.store'), [
                'student_id' => $student->id,
                'search' => $student->name,
                'bill_keys' => [$sppKey, $registrationKey],
                'payment_month_counts' => [$student->id.'_spp' => 1],
                'allocation_amounts' => [$sppKey => 120000, $registrationKey => 0],
                'payment_method' => 'Cash',
                'paid_amount' => 120000,
            ])
            ->assertRedirect(route('finance.payments.index', ['search' => $student->name]))
            ->assertSessionHasErrors('allocation_amounts.'.$sppKey);

        $this->assertDatabaseCount('spp_payments', 0);
        $this->assertDatabaseCount('other_payments', 0);
    }

    public function test_spp_paid_through_current_month_shows_zero_but_allows_next_month_payment(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-20 10:00:00'));

        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '7A', 'level' => 'Kelas 7']);
        $student = Student::create([
            'nis' => 'SPP-LUNAS-BULAN-INI',
            'name' => 'Siswa Lunas Bulan Ini',
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
            'code' => 'SPP-LUNAS-BULAN-INI',
            'name' => 'SPP Lunas Bulan Ini',
            'amount' => 600000,
            'period' => 'Bulanan',
            'creates_bill' => true,
            'is_active' => true,
        ]);
        $payment = SppPayment::create([
            'student_id' => $student->id,
            'transaction_at' => '2026-09-20 09:00:00',
            'payment_method' => 'Cash',
            'status' => 'Diterima',
            'original_amount' => 1800000,
            'discount_amount' => 0,
            'total_amount' => 1800000,
            'paid_amount' => 1800000,
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);

        foreach ([7, 8, 9] as $month) {
            $payment->items()->create([
                'student_id' => $student->id,
                'year' => 2026,
                'month' => $month,
                'original_amount' => 600000,
                'discount_amount' => 0,
                'total_amount' => 600000,
                'paid_amount' => 600000,
                'remaining_amount' => 0,
                'payment_status' => 'Lunas',
            ]);
        }

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get('/keuangan/pembayaran?search='.urlencode($student->name))
            ->assertOk()
            ->assertDontSee('Bayar sampai')
            ->assertSee('NOMINAL PEMBAYARAN')
            ->assertSee('Oktober 2026');

        $this->post('/keuangan/pembayaran', [
            'student_id' => $student->id,
            'search' => $student->name,
            'bill_keys' => [$student->id.':spp'],
            'payment_month_counts' => [$student->id.'_spp' => 1],
            'payment_method' => 'Cash',
            'paid_amount' => 600000,
        ])->assertRedirect();

        $this->assertDatabaseHas('spp_payment_items', [
            'student_id' => $student->id,
            'year' => 2026,
            'month' => 10,
            'paid_amount' => 600000,
            'payment_status' => 'Lunas',
        ]);
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
            ->assertDontSee('Bayar sampai')
            ->assertSee('Juli 2026')
            ->assertDontSee('Juli 2026 - Agustus 2026');

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

    public function test_transaction_hub_can_pay_part_of_old_spp_arrears(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-06-05 10:00:00'));

        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '7A', 'level' => 'Kelas 7']);
        $student = Student::create([
            'nis' => 'SPP-TUNGGAKAN',
            'name' => 'Siswa Tunggakan SPP',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'billing_start_date' => '2025-09-01',
            'is_active' => true,
        ]);
        FeeType::create([
            'education_unit_id' => $unit->id,
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'spp',
            'code' => 'SPP-TUNGGAKAN',
            'name' => 'SPP Tunggakan',
            'amount' => 100000,
            'period' => 'Bulanan',
            'creates_bill' => true,
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create());
        $this->get('/keuangan/pembayaran?search='.urlencode($student->name))
            ->assertOk()
            ->assertDontSee('Bayar sampai')
            ->assertSee('September 2025 - Juni 2026')
            ->assertSee('NOMINAL PEMBAYARAN')
            ->assertSee('name="payment_month_counts['.$student->id.'_spp]" value="10"', false);

        $this->post('/keuangan/pembayaran', [
            'student_id' => $student->id,
            'search' => $student->name,
            'bill_keys' => [$student->id.':spp'],
            'payment_month_counts' => [$student->id.'_spp' => 2],
            'payment_method' => 'Cash',
            'paid_amount' => 200000,
        ])->assertRedirect();

        $this->assertDatabaseCount('spp_payment_items', 2);
        $this->assertDatabaseHas('spp_payment_items', ['year' => 2025, 'month' => 9, 'paid_amount' => 100000]);
        $this->assertDatabaseHas('spp_payment_items', ['year' => 2025, 'month' => 10, 'paid_amount' => 100000]);
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
            ->assertSee('Jumlah Penerimaan')
            ->assertSee('Rp 0')
            ->assertSee('0 transaksi diterima');

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
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payments.other.update',
            'subject_type' => OtherPayment::class,
            'subject_id' => $payment->id,
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
            ->assertSee('@page { size: A4 portrait; margin: 5mm; }', false)
            ->assertSee('data-a4-preview-viewport', false)
            ->assertSee('width: 210mm; height: 297mm;', false)
            ->assertSee('width: 215.9mm; height: 110mm;', false)
            ->assertSee('width: 200mm; height: 101.9mm;', false)
            ->assertSee('transform: scale(.9263557)', false)
            ->assertSee('Tahun Pelajaran')
            ->assertSee('Keringanan (Rp)')
            ->assertSee("window.addEventListener('load', () => window.print())", false);

        $this->delete(route('finance.other.destroy', $payment))
            ->assertRedirect('/keuangan/pembayaran/lain-lain?category=daftar-ulang');
        $this->assertDatabaseMissing('other_payments', ['id' => $payment->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payments.other.delete',
            'subject_type' => OtherPayment::class,
            'subject_id' => $payment->id,
        ]);
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

    public function test_unit_scoped_cashier_cannot_access_spp_student_or_payment_from_another_unit(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $assignedUnit = EducationUnit::create(['code' => 'RA', 'name' => 'RA Mambaul Hikmah', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Mambaul Hikmah', 'is_active' => true]);
        SchoolClass::create(['education_unit_id' => $assignedUnit->id, 'name' => 'A1', 'level' => 'A1', 'is_active' => true]);
        $otherClass = SchoolClass::create(['education_unit_id' => $otherUnit->id, 'name' => 'I A', 'level' => 'Kelas I', 'is_active' => true]);
        $otherStudent = Student::create([
            'nis' => '880001',
            'name' => 'Siswa Unit Lain',
            'gender' => 'L',
            'school_class_id' => $otherClass->id,
            'academic_year_id' => $year->id,
            'billing_start_date' => '2026-01-01',
            'is_active' => true,
        ]);

        FeeType::create([
            'education_unit_id' => $otherUnit->id,
            'payment_group' => 'spp',
            'code' => 'SPP-MI-TEST',
            'name' => 'SPP MI Test',
            'amount' => 100000,
            'period' => 'Bulanan',
            'is_active' => true,
        ]);
        $payment = SppPayment::create([
            'student_id' => $otherStudent->id,
            'transaction_at' => '2026-07-01 08:00:00',
            'payment_method' => 'Cash',
            'status' => 'Diterima',
            'original_amount' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'paid_amount' => 100000,
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);
        $payment->items()->create([
            'student_id' => $otherStudent->id,
            'year' => 2026,
            'month' => 7,
            'original_amount' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'paid_amount' => 100000,
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);

        $this->actingAs($this->scopedCashier($assignedUnit));

        $this->getJson('/keuangan/pembayaran/spp/quote?student_id='.$otherStudent->id.'&month_count=1')->assertForbidden();
        $this->getJson('/keuangan/pembayaran/spp/months?student_id='.$otherStudent->id)->assertForbidden();
        $this->post('/keuangan/pembayaran/spp', [
            'transaction_date' => '2026-07-02',
            'transaction_time' => '08:30:00',
            'student_id' => $otherStudent->id,
            'month_count' => 1,
            'payment_method' => 'Cash',
            'status' => 'Diterima',
            'paid_amount' => 100000,
        ])->assertForbidden();
        $this->getJson(route('finance.spp.show', $payment))->assertForbidden();
        $this->get(route('finance.spp.receipt', $payment))->assertForbidden();
        $this->put(route('finance.spp.update', $payment), [
            'transaction_date' => '2026-07-02',
            'transaction_time' => '08:30:00',
            'payment_method' => 'Cash',
            'status' => 'Diterima',
        ])->assertForbidden();
        $this->post(route('finance.spp.correct', $payment), [
            'new_paid_amount' => 50000,
            'reason' => 'Test akses lintas unit',
        ])->assertForbidden();
        $this->delete(route('finance.spp.destroy', $payment))->assertForbidden();

        $this->assertDatabaseHas('spp_payments', ['id' => $payment->id, 'paid_amount' => 100000]);
    }

    public function test_spp_payment_can_be_corrected_and_cancelled_without_deleting_history(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        [$year, $unit, $class, $student] = $this->paymentCorrectionContext();
        FeeType::create([
            'education_unit_id' => $unit->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'spp',
            'code' => 'SPP-CORRECTION',
            'name' => 'SPP Koreksi',
            'amount' => 100000,
            'period' => 'Bulanan',
            'creates_bill' => true,
            'is_active' => true,
        ]);
        $bill = Bill::create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'source_type' => 'spp',
            'generation_key' => hash('sha256', "spp|{$student->id}|2026|7"),
            'year' => 2026,
            'month' => 7,
            'title' => 'SPP Juli 2026',
            'issue_date' => '2026-07-01',
            'due_date' => '2026-07-31',
            'original_amount' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'paid_amount' => 100000,
            'remaining_amount' => 0,
            'status' => 'Lunas',
        ]);
        $payment = SppPayment::create([
            'student_id' => $student->id,
            'transaction_at' => '2026-07-10 08:00:00',
            'payment_method' => 'Cash',
            'status' => 'Diterima',
            'original_amount' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'paid_amount' => 100000,
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);
        SppPaymentItem::create([
            'spp_payment_id' => $payment->id,
            'student_id' => $student->id,
            'year' => 2026,
            'month' => 7,
            'original_amount' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'paid_amount' => 100000,
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);
        app(BillService::class)->syncSppPayment($payment->load(['student.academicYear', 'student.schoolClass.educationUnit', 'items']));

        $this->post(route('finance.spp.correct', $payment), [
            'transaction_date' => '2026-07-11',
            'transaction_time' => '09.30',
            'payment_method' => 'Transfer',
            'status' => 'Diterima',
            'new_paid_amount' => 60000,
            'reason' => 'Nominal salah input',
            'return_url' => '/laporan/transaksi',
        ])->assertRedirect('/laporan/transaksi');

        $this->assertDatabaseHas('spp_payments', [
            'id' => $payment->id,
            'transaction_at' => '2026-07-11 09:30:00',
            'payment_method' => 'Transfer',
            'paid_amount' => 60000,
            'remaining_amount' => 40000,
            'payment_status' => 'Belum Lunas',
        ]);
        $this->assertDatabaseHas('bills', [
            'id' => $bill->id,
            'paid_amount' => 60000,
            'remaining_amount' => 40000,
            'status' => 'Sebagian',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payments.spp.correct',
            'subject_type' => SppPayment::class,
            'subject_id' => $payment->id,
        ]);

        $this->post(route('finance.spp.cancel', $payment), [
            'reason' => 'Tidak jadi bayar',
            'return_url' => '/laporan/transaksi',
        ])->assertRedirect('/laporan/transaksi');

        $this->assertDatabaseHas('spp_payments', [
            'id' => $payment->id,
            'status' => 'Dibatalkan',
            'paid_amount' => 0,
            'remaining_amount' => 100000,
            'payment_status' => 'Dibatalkan',
        ]);
        $this->assertDatabaseHas('spp_payment_items', [
            'spp_payment_id' => $payment->id,
            'paid_amount' => 0,
            'remaining_amount' => 100000,
            'payment_status' => 'Dibatalkan',
        ]);
        $this->assertDatabaseHas('bills', [
            'id' => $bill->id,
            'paid_amount' => 0,
            'remaining_amount' => 100000,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payments.spp.cancel',
            'subject_type' => SppPayment::class,
            'subject_id' => $payment->id,
        ]);
        $this->get('/laporan/transaksi?date_from=2026-07-11&date_to=2026-07-11&payment_status=Dibatalkan')
            ->assertOk()
            ->assertSee('Dibatalkan')
            ->assertSee('Siswa Koreksi');
    }

    public function test_other_payment_can_be_corrected_and_cancelled_without_deleting_history(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        [$year, $unit, $class, $student] = $this->paymentCorrectionContext();
        $feeType = FeeType::create([
            'education_unit_id' => $unit->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'daftar-ulang',
            'code' => 'DU-CORRECTION',
            'name' => 'Daftar Ulang Koreksi',
            'amount' => 500000,
            'period' => 'Sekali Bayar',
            'creates_bill' => true,
            'is_active' => true,
        ]);
        $payment = OtherPayment::create([
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'transaction_at' => '2026-07-10 08:00:00',
            'payment_method' => 'Cash',
            'status' => 'Diterima',
            'original_amount' => 500000,
            'discount_amount' => 0,
            'total_amount' => 500000,
            'paid_amount' => 500000,
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);
        app(BillService::class)->syncOtherPayment($payment->load(['student.academicYear', 'student.schoolClass.educationUnit', 'feeType']));
        $bill = Bill::where('source_type', 'fee_type')->where('student_id', $student->id)->firstOrFail();

        $this->post(route('finance.other.correct', $payment), [
            'transaction_date' => '2026-07-11',
            'transaction_time' => '10.15',
            'payment_method' => 'Transfer',
            'status' => 'Diterima',
            'new_paid_amount' => 300000,
            'reason' => 'Nominal daftar ulang salah',
            'return_url' => '/laporan/transaksi',
        ])->assertRedirect('/laporan/transaksi');

        $this->assertDatabaseHas('other_payments', [
            'id' => $payment->id,
            'transaction_at' => '2026-07-11 10:15:00',
            'payment_method' => 'Transfer',
            'paid_amount' => 300000,
            'remaining_amount' => 200000,
            'payment_status' => 'Belum Lunas',
        ]);
        $this->assertDatabaseHas('bills', [
            'id' => $bill->id,
            'paid_amount' => 300000,
            'remaining_amount' => 200000,
            'status' => 'Sebagian',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payments.other.correct',
            'subject_type' => OtherPayment::class,
            'subject_id' => $payment->id,
        ]);

        $this->post(route('finance.other.cancel', $payment), [
            'reason' => 'Transaksi dibatalkan wali',
            'return_url' => '/laporan/transaksi',
        ])->assertRedirect('/laporan/transaksi');

        $this->assertDatabaseHas('other_payments', [
            'id' => $payment->id,
            'status' => 'Dibatalkan',
            'paid_amount' => 0,
            'remaining_amount' => 500000,
            'payment_status' => 'Dibatalkan',
        ]);
        $this->assertDatabaseHas('bills', [
            'id' => $bill->id,
            'paid_amount' => 0,
            'remaining_amount' => 500000,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payments.other.cancel',
            'subject_type' => OtherPayment::class,
            'subject_id' => $payment->id,
        ]);
    }

    public function test_unit_scoped_cashier_cannot_access_other_payment_student_or_payment_from_another_unit(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $assignedUnit = EducationUnit::create(['code' => 'RA', 'name' => 'RA Mambaul Hikmah', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'PONPES', 'name' => 'PONPES Mambaul Hikmah', 'is_active' => true]);
        SchoolClass::create(['education_unit_id' => $assignedUnit->id, 'name' => 'A1', 'level' => 'A1', 'is_active' => true]);
        $otherClass = SchoolClass::create(['education_unit_id' => $otherUnit->id, 'name' => 'Asrama', 'level' => 'Asrama', 'is_active' => true]);
        $otherStudent = Student::create([
            'nis' => '880002',
            'name' => 'Santri Unit Lain',
            'gender' => 'P',
            'school_class_id' => $otherClass->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $registrationFee = FeeType::create([
            'education_unit_id' => $otherUnit->id,
            'payment_group' => 'daftar-ulang',
            'code' => 'DU-PONPES-TEST',
            'name' => 'Daftar Ulang PONPES Test',
            'amount' => 500000,
            'period' => 'Sekali Bayar',
            'is_active' => true,
        ]);
        $laundryFee = FeeType::create([
            'education_unit_id' => $otherUnit->id,
            'payment_group' => 'laundry',
            'code' => 'LAUNDRY-PONPES-TEST',
            'name' => 'Laundry PONPES Test',
            'amount' => 50000,
            'period' => 'Bulanan',
            'is_active' => true,
        ]);
        $payment = OtherPayment::create([
            'student_id' => $otherStudent->id,
            'fee_type_id' => $registrationFee->id,
            'transaction_at' => '2026-07-01 08:00:00',
            'payment_method' => 'Cash',
            'status' => 'Diterima',
            'original_amount' => 500000,
            'discount_amount' => 0,
            'total_amount' => 500000,
            'paid_amount' => 500000,
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);

        $this->actingAs($this->scopedCashier($assignedUnit));

        $this->getJson('/keuangan/pembayaran/lain-lain/quote?category=daftar-ulang&student_id='.$otherStudent->id.'&fee_type_id='.$registrationFee->id)->assertForbidden();
        $this->getJson('/keuangan/pembayaran/lain-lain/months?category=laundry&student_id='.$otherStudent->id.'&fee_type_id='.$laundryFee->id.'&year=2026')->assertForbidden();
        $this->post('/keuangan/pembayaran/lain-lain?category=daftar-ulang', [
            'transaction_date' => '2026-07-02',
            'transaction_time' => '08:30:00',
            'student_id' => $otherStudent->id,
            'fee_type_id' => $registrationFee->id,
            'payment_method' => 'Cash',
            'status' => 'Diterima',
            'paid_amount' => 500000,
        ])->assertForbidden();
        $this->getJson(route('finance.other.show', $payment))->assertForbidden();
        $this->get(route('finance.other.receipt', $payment))->assertForbidden();
        $this->put(route('finance.other.update', $payment), [
            'transaction_date' => '2026-07-02',
            'transaction_time' => '08:30:00',
            'payment_method' => 'Cash',
            'status' => 'Diterima',
        ])->assertForbidden();
        $this->delete(route('finance.other.destroy', $payment))->assertForbidden();

        $this->assertDatabaseHas('other_payments', ['id' => $payment->id, 'paid_amount' => 500000]);
    }

    public function test_transfer_payment_proof_is_stored_privately_and_served_with_payment_access(): void
    {
        config(['payments.transfer_proof_upload_enabled' => true]);
        Storage::fake('local');
        Storage::fake('public');
        CarbonImmutable::setTestNow('2026-07-15 09:00:00');

        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Mambaul Hikmah', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'RA', 'name' => 'RA Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'I A', 'level' => 'Kelas I', 'is_active' => true]);
        SchoolClass::create(['education_unit_id' => $otherUnit->id, 'name' => 'A1', 'level' => 'A1', 'is_active' => true]);
        $student = Student::create([
            'nis' => '880003',
            'name' => 'Siswa Transfer Proof',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'billing_start_date' => '2026-07-01',
            'is_active' => true,
        ]);
        FeeType::create([
            'education_unit_id' => $unit->id,
            'payment_group' => 'spp',
            'code' => 'SPP-MI-PROOF',
            'name' => 'SPP MI Proof',
            'amount' => 100000,
            'period' => 'Bulanan',
            'is_active' => true,
        ]);
        $drive = Mockery::mock(GoogleDriveStorageService::class);
        $drive->shouldReceive('upload')->once()->andReturn([
            'file_id' => 'drive-proof-123',
            'metadata' => ['name' => 'payment-proof.pdf', 'mime_type' => 'application/pdf', 'size' => 12, 'uploaded_at' => '2026-07-15T09:00:00+07:00'],
        ]);
        $drive->shouldReceive('download')->once()->with('drive-proof-123')->andReturn('proof');
        $this->app->instance(GoogleDriveStorageService::class, $drive);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('finance.payments.store'), [
                'student_id' => $student->id,
                'search' => $student->name,
                'bill_keys' => [$student->id.':spp'],
                'payment_method' => 'Transfer',
                'paid_amount' => 100000,
                'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 12, 'application/pdf'),
            ])
            ->assertRedirect(route('finance.payments.index', [
                'search' => $student->name,
                'student_id' => $student->id,
                'registration_id' => $student->id,
            ]));

        $payment = SppPayment::firstOrFail();
        $this->assertNull($payment->transfer_proof_path);
        $this->assertSame('drive-proof-123', $payment->transfer_proof_file_id);
        $this->assertSame('payment-proof.pdf', $payment->transfer_proof_metadata['name']);
        $this->assertSame([], Storage::disk('local')->allFiles('payment-proofs'));

        $this->get(route('finance.spp.proof', $payment))->assertOk();

        $this->actingAs($this->scopedCashier($otherUnit))
            ->get(route('finance.spp.proof', $payment))
            ->assertForbidden();

        CarbonImmutable::setTestNow();
    }

    public function test_scoped_cashier_bulk_payment_cannot_include_linked_student_from_another_unit(): void
    {
        CarbonImmutable::setTestNow('2026-07-15 09:00:00');

        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $assignedUnit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Mambaul Hikmah', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'PONPES', 'name' => 'PONPES Mambaul Hikmah', 'is_active' => true]);
        $assignedClass = SchoolClass::create(['education_unit_id' => $assignedUnit->id, 'name' => '1A', 'level' => 'Kelas 1', 'is_active' => true]);
        $otherClass = SchoolClass::create(['education_unit_id' => $otherUnit->id, 'name' => 'Asrama', 'level' => 'Asrama', 'is_active' => true]);
        $identity = Student::create([
            'nis' => 'BULK-001',
            'name' => 'Siswa Bulk Scope',
            'gender' => 'L',
            'school_class_id' => $assignedClass->id,
            'academic_year_id' => $year->id,
            'billing_start_date' => '2026-07-01',
            'is_active' => true,
        ]);
        $linkedOtherUnit = Student::create([
            'identity_student_id' => $identity->id,
            'nis' => 'BULK-PP',
            'name' => 'Siswa Bulk Scope',
            'gender' => 'L',
            'school_class_id' => $otherClass->id,
            'academic_year_id' => $year->id,
            'billing_start_date' => '2026-07-01',
            'is_active' => true,
        ]);
        FeeType::create(['education_unit_id' => $assignedUnit->id, 'academic_year_id' => $year->id, 'payment_group' => 'spp', 'code' => 'SPP-MI-BULK', 'name' => 'SPP MI Bulk', 'amount' => 100000, 'period' => 'Bulanan', 'creates_bill' => true, 'is_active' => true]);
        FeeType::create(['education_unit_id' => $otherUnit->id, 'academic_year_id' => $year->id, 'payment_group' => 'spp', 'code' => 'SPP-PP-BULK', 'name' => 'SPP PP Bulk', 'amount' => 100000, 'period' => 'Bulanan', 'creates_bill' => true, 'is_active' => true]);

        $this->actingAs($this->scopedCashier($assignedUnit))
            ->post(route('finance.payments.store'), [
                'student_id' => $identity->id,
                'search' => $identity->name,
                'bill_keys' => [$identity->id.':spp', $linkedOtherUnit->id.':spp'],
                'payment_method' => 'Cash',
                'paid_amount' => 100000,
            ])
            ->assertRedirect(route('finance.payments.index', [
                'search' => $identity->name,
                'student_id' => $identity->id,
                'registration_id' => $identity->id,
            ]));

        $this->assertDatabaseHas('spp_payments', ['student_id' => $identity->id, 'paid_amount' => 100000]);
        $this->assertDatabaseMissing('spp_payments', ['student_id' => $linkedOtherUnit->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payments.bulk_create',
            'student_count' => 1,
        ]);

        CarbonImmutable::setTestNow();
    }

    public function test_other_payment_import_is_unit_scoped_and_audited(): void
    {
        Storage::fake('local');
        CarbonImmutable::setTestNow('2026-07-15 09:00:00');

        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $assignedUnit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Mambaul Hikmah', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'MA', 'name' => 'MA Mambaul Hikmah', 'is_active' => true]);
        $assignedClass = SchoolClass::create(['education_unit_id' => $assignedUnit->id, 'name' => '1A', 'level' => 'Kelas 1', 'is_active' => true]);
        $otherClass = SchoolClass::create(['education_unit_id' => $otherUnit->id, 'name' => '10A', 'level' => 'Kelas 10', 'is_active' => true]);
        $assignedStudent = Student::create(['nis' => 'IMP-001', 'name' => 'Siswa Import Unit', 'gender' => 'L', 'school_class_id' => $assignedClass->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        $otherStudent = Student::create(['nis' => 'IMP-002', 'name' => 'Siswa Import Lain', 'gender' => 'P', 'school_class_id' => $otherClass->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        FeeType::create(['education_unit_id' => $assignedUnit->id, 'academic_year_id' => $year->id, 'payment_group' => 'daftar-ulang', 'code' => 'DU-MI-IMPORT', 'name' => 'Daftar Ulang Import', 'amount' => 100000, 'period' => 'Sekali Bayar', 'creates_bill' => true, 'is_active' => true]);
        FeeType::create(['education_unit_id' => $otherUnit->id, 'academic_year_id' => $year->id, 'payment_group' => 'daftar-ulang', 'code' => 'DU-MA-IMPORT', 'name' => 'Daftar Ulang Import', 'amount' => 100000, 'period' => 'Sekali Bayar', 'creates_bill' => true, 'is_active' => true]);

        $path = tempnam(sys_get_temp_dir(), 'other-import-');
        StudentXlsx::write($path, [
            ['NIS', 'Nama', 'Kategori Pembayaran', 'Jenis Pendidikan', 'Kelas', 'Cara Bayar', 'Nominal', 'Waktu', 'Petugas'],
            [$assignedStudent->nis, $assignedStudent->name, 'Daftar Ulang Import', 'MI', '1A', 'Cash', '100000', '2026-07-15 09:00:00', 'Tester'],
            [$otherStudent->nis, $otherStudent->name, 'Daftar Ulang Import', 'MA', '10A', 'Cash', '100000', '2026-07-15 09:05:00', 'Tester'],
        ]);
        $token = '11111111-1111-4111-8111-111111111111';
        Storage::disk('local')->put('other-payment-imports/'.$token.'.xlsx', file_get_contents($path));
        @unlink($path);

        $this->actingAs($this->scopedCashier($assignedUnit))
            ->withSession([
                'other_payment_imports.'.$token => [
                    'path' => 'other-payment-imports/'.$token.'.xlsx',
                    'name' => 'other-payments.xlsx',
                    'mappings' => [],
                ],
            ])
            ->post(route('finance.other.import', ['category' => 'daftar-ulang']), ['token' => $token])
            ->assertRedirect(route('finance.payments.import'));

        $this->assertDatabaseHas('other_payments', ['student_id' => $assignedStudent->id, 'paid_amount' => 100000]);
        $this->assertDatabaseMissing('other_payments', ['student_id' => $otherStudent->id]);
        $audit = AuditLog::where('action', 'payments.other.import')->firstOrFail();
        $this->assertSame(1, (int) $audit->metadata['imported']);
        $this->assertSame(1, (int) $audit->metadata['failed_rows']);

        CarbonImmutable::setTestNow();
    }

    public function test_other_payment_proof_is_served_with_payment_access(): void
    {
        Storage::fake('local');

        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'PONPES Mambaul Hikmah', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'RA', 'name' => 'RA Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'Asrama', 'level' => 'Asrama', 'is_active' => true]);
        SchoolClass::create(['education_unit_id' => $otherUnit->id, 'name' => 'A1', 'level' => 'A1', 'is_active' => true]);
        $student = Student::create([
            'nis' => '880004',
            'name' => 'Santri Proof',
            'gender' => 'P',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $feeType = FeeType::create([
            'education_unit_id' => $unit->id,
            'payment_group' => 'daftar-ulang',
            'code' => 'DU-PROOF',
            'name' => 'Daftar Ulang Proof',
            'amount' => 500000,
            'period' => 'Sekali Bayar',
            'is_active' => true,
        ]);
        Storage::disk('local')->put('payment-proofs/other-proof.pdf', 'proof');
        $payment = OtherPayment::create([
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'transaction_at' => '2026-07-01 08:00:00',
            'payment_method' => 'Transfer',
            'transfer_proof_path' => 'payment-proofs/other-proof.pdf',
            'status' => 'Diterima',
            'original_amount' => 500000,
            'discount_amount' => 0,
            'total_amount' => 500000,
            'paid_amount' => 500000,
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.other.proof', $payment))
            ->assertOk();

        $this->actingAs($this->scopedCashier($otherUnit))
            ->get(route('finance.other.proof', $payment))
            ->assertForbidden();
    }

    private function multiUnitPaymentContext(): array
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $mtsUnit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $pondokUnit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $mtsClass = SchoolClass::create(['education_unit_id' => $mtsUnit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $pondokClass = SchoolClass::create(['education_unit_id' => $pondokUnit->id, 'name' => 'Asrama A', 'level' => 'Asrama']);
        $identity = Student::create([
            'nis' => 'MULTI-MTS', 'name' => 'Siswa Multi Unit', 'gender' => 'L',
            'school_class_id' => $mtsClass->id, 'academic_year_id' => $year->id,
            'billing_start_date' => '2026-07-01', 'is_active' => true,
        ]);
        $pondokRegistration = Student::create([
            'identity_student_id' => $identity->id, 'nis' => 'MULTI-PONPES', 'name' => $identity->name, 'gender' => 'L',
            'school_class_id' => $pondokClass->id, 'academic_year_id' => $year->id,
            'billing_start_date' => '2026-07-01', 'is_active' => true,
        ]);
        FeeType::create([
            'education_unit_id' => $mtsUnit->id, 'academic_year_id' => $year->id,
            'payment_group' => 'spp', 'code' => 'SPP-MULTI-MTS', 'name' => 'SPP MTs',
            'amount' => 100000, 'period' => 'Bulanan', 'creates_bill' => true, 'is_active' => true,
        ]);
        FeeType::create([
            'education_unit_id' => $pondokUnit->id, 'academic_year_id' => $year->id,
            'payment_group' => 'spp', 'code' => 'SPP-MULTI-PONPES', 'name' => 'SPP PONPES',
            'amount' => 200000, 'period' => 'Bulanan', 'creates_bill' => true, 'is_active' => true,
        ]);

        return compact('year', 'mtsUnit', 'pondokUnit', 'mtsClass', 'pondokClass', 'identity', 'pondokRegistration');
    }

    private function scopedCashier(EducationUnit $unit): User
    {
        Role::updateOrCreate(['key' => 'kasir'], [
            'name' => 'Kasir',
            'permissions' => ['payments.cash.create', 'payments.view_unit', 'payments.verify_transfer'],
            'is_active' => true,
        ]);

        $user = User::factory()->create(['role' => 'kasir']);
        $user->educationUnits()->attach($unit->id);

        return $user;
    }

    private function paymentCorrectionContext(): array
    {
        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true, 'start_date' => '2026-07-01', 'end_date' => '2027-06-30']);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'I A', 'level' => 'Kelas I', 'is_active' => true]);
        $student = Student::create([
            'nis' => '990001',
            'name' => 'Siswa Koreksi',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'billing_start_date' => '2026-07-01',
            'is_active' => true,
        ]);

        return [$year, $unit, $class, $student];
    }
}
