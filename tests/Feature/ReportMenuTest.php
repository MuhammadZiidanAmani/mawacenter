<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
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
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportMenuTest extends TestCase
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

        Role::updateOrCreate(['key' => 'bendahara'], [
            'name' => 'Bendahara Unit',
            'permissions' => Role::defaultPermissionsFor('bendahara'),
            'is_active' => true,
        ]);
    }

    public function test_transaction_report_paginates_and_sorts_safe_columns(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        [$year, $unit, $class] = $this->schoolContext();

        foreach (range(1, 12) as $index) {
            $student = Student::create([
                'nis' => '90'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'name' => 'Siswa Laporan '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'gender' => 'L',
                'school_class_id' => $class->id,
                'academic_year_id' => $year->id,
                'is_active' => true,
            ]);

            SppPayment::create([
                'student_id' => $student->id,
                'transaction_at' => '2026-06-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT).' 08:00:00',
                'payment_method' => 'Cash',
                'status' => 'Diterima',
                'original_amount' => 100000 + $index,
                'discount_amount' => 0,
                'total_amount' => 100000 + $index,
                'paid_amount' => 100000 + $index,
                'remaining_amount' => 0,
                'payment_status' => 'Lunas',
            ]);
        }

        $this->get('/laporan/transaksi?date_from=2026-06-01&date_to=2026-06-30&per_page=10&sort=amount&direction=asc')
            ->assertOk()
            ->assertSee('pagination-wrap', false)
            ->assertSee('Siswa Laporan 01')
            ->assertDontSee('Siswa Laporan 12')
            ->assertSee('sort=amount', false)
            ->assertSee('direction=desc', false);

        $this->get('/laporan/transaksi?date_from=2026-06-01&date_to=2026-06-30&per_page=10&sort=amount&direction=asc&page=2')
            ->assertOk()
            ->assertSee('Siswa Laporan 11');
    }

    public function test_transaction_report_defaults_date_from_to_today(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-15 09:00:00'));

        try {
            $this->actingAs(User::factory()->create(['role' => 'admin']));
            [$year, $unit, $class] = $this->schoolContext();
            $earlyStudent = Student::create([
                'nis' => '9015',
                'name' => 'Siswa Awal Bulan',
                'gender' => 'L',
                'school_class_id' => $class->id,
                'academic_year_id' => $year->id,
                'is_active' => true,
            ]);
            $todayStudent = Student::create([
                'nis' => '9016',
                'name' => 'Siswa Hari Ini',
                'gender' => 'L',
                'school_class_id' => $class->id,
                'academic_year_id' => $year->id,
                'is_active' => true,
            ]);

            SppPayment::create([
                'student_id' => $earlyStudent->id,
                'transaction_at' => '2026-06-01 08:00:00',
                'payment_method' => 'Cash',
                'status' => 'Diterima',
                'original_amount' => 100000,
                'discount_amount' => 0,
                'total_amount' => 100000,
                'paid_amount' => 100000,
                'remaining_amount' => 0,
                'payment_status' => 'Lunas',
            ]);
            SppPayment::create([
                'student_id' => $todayStudent->id,
                'transaction_at' => '2026-06-15 08:00:00',
                'payment_method' => 'Cash',
                'status' => 'Diterima',
                'original_amount' => 100000,
                'discount_amount' => 0,
                'total_amount' => 100000,
                'paid_amount' => 100000,
                'remaining_amount' => 0,
                'payment_status' => 'Lunas',
            ]);

            $this->get('/laporan/transaksi')
                ->assertOk()
                ->assertSee('name="date_from"', false)
                ->assertSee('value="2026-06-15"', false)
                ->assertSee('Siswa Hari Ini')
                ->assertDontSee('Siswa Awal Bulan');
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_transaction_unit_summary_shows_cash_and_transfer_totals(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        [$year, $unit, $class] = $this->schoolContext();
        $cashStudent = Student::create([
            'nis' => '9021',
            'name' => 'Siswa Cash',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $transferStudent = Student::create([
            'nis' => '9022',
            'name' => 'Siswa Transfer',
            'gender' => 'P',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);

        SppPayment::create([
            'student_id' => $cashStudent->id,
            'transaction_at' => '2026-06-15 08:00:00',
            'payment_method' => 'Cash',
            'status' => 'Diterima',
            'original_amount' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'paid_amount' => 100000,
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);
        SppPayment::create([
            'student_id' => $transferStudent->id,
            'transaction_at' => '2026-06-15 09:00:00',
            'payment_method' => 'Transfer',
            'status' => 'Diterima',
            'original_amount' => 250000,
            'discount_amount' => 0,
            'total_amount' => 250000,
            'paid_amount' => 250000,
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);

        $this->get('/laporan/transaksi?date_from=2026-06-15&date_to=2026-06-15')
            ->assertOk()
            ->assertSeeInOrder(['Unit Pendidikan', 'Jumlah Transaksi', 'Cash', 'Transfer', 'Jumlah Penerimaan'])
            ->assertSee('Total Keseluruhan')
            ->assertSee('Rp 100.000')
            ->assertSee('Rp 250.000')
            ->assertSee('Rp 350.000');
    }

    public function test_student_search_filters_relevant_reports_and_is_hidden_on_unit_recap(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        [$year, $unit, $class] = $this->schoolContext();
        $target = Student::create([
            'nis' => '9401',
            'nisn' => '0094001',
            'name' => 'Siswa Dicari',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $other = Student::create([
            'nis' => '9402',
            'nisn' => '0094002',
            'name' => 'Siswa Lain',
            'gender' => 'P',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);

        foreach ([[$target, 100000], [$other, 200000]] as [$student, $amount]) {
            Bill::create([
                'student_id' => $student->id,
                'academic_year_id' => $year->id,
                'source_type' => 'spp',
                'generation_key' => 'spp-'.$student->nis.'-2026-6',
                'year' => 2026,
                'month' => 6,
                'title' => 'SPP Juni 2026',
                'issue_date' => '2026-06-01',
                'due_date' => '2026-06-30',
                'original_amount' => $amount,
                'discount_amount' => 0,
                'total_amount' => $amount,
                'paid_amount' => $amount,
                'remaining_amount' => 0,
                'status' => 'Lunas',
            ]);

            $payment = SppPayment::create([
                'student_id' => $student->id,
                'transaction_at' => '2026-06-15 08:00:00',
                'payment_method' => 'Cash',
                'status' => 'Diterima',
                'original_amount' => $amount,
                'discount_amount' => 0,
                'total_amount' => $amount,
                'paid_amount' => $amount,
                'remaining_amount' => 0,
                'payment_status' => 'Lunas',
            ]);
            SppPaymentItem::create([
                'spp_payment_id' => $payment->id,
                'student_id' => $student->id,
                'year' => 2026,
                'month' => 6,
                'original_amount' => $amount,
                'discount_amount' => 0,
                'total_amount' => $amount,
                'paid_amount' => $amount,
                'remaining_amount' => 0,
                'payment_status' => 'Lunas',
            ]);
        }

        $this->get('/laporan/transaksi?date_from=2026-06-15&date_to=2026-06-15&student_search=0094001')
            ->assertOk()
            ->assertSee('report-student-search-card is-selected', false)
            ->assertSee('Siswa Dicari')
            ->assertSee('9401')
            ->assertDontSee('Siswa Lain');

        $this->get('/laporan/spp-perbulan?year=2026&month=6&student_search=0094001')
            ->assertOk()
            ->assertSee('Siswa Dicari')
            ->assertDontSee('Siswa Lain');

        $this->get('/laporan/spp-tahun-pelajaran?academic_year_id='.$year->id.'&student_search=Siswa Dicari')
            ->assertOk()
            ->assertSee('Siswa Dicari')
            ->assertDontSee('Siswa Lain');

        $this->get('/laporan/rekap-unit?date_from=2026-06-15&date_to=2026-06-15&academic_year_id='.$year->id.'&student_search=0094001')
            ->assertOk()
            ->assertDontSee('report-student-search-card', false)
            ->assertSee('Rp 300.000');
    }

    public function test_monthly_spp_uses_calendar_year_without_active_academic_year_scope(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $oldYear = AcademicYear::create(['name' => '2025/2026', 'is_active' => false]);
        AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create([
            'nis' => '9101',
            'name' => 'Siswa Tahun Lama',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $oldYear->id,
            'is_active' => true,
        ]);
        $unpaidStudent = Student::create([
            'nis' => '9102',
            'name' => 'Siswa Belum Bayar',
            'gender' => 'P',
            'school_class_id' => $class->id,
            'academic_year_id' => $oldYear->id,
            'is_active' => true,
        ]);

        Bill::create([
            'student_id' => $student->id,
            'academic_year_id' => $oldYear->id,
            'source_type' => 'spp',
            'generation_key' => 'spp-9101-2026-6',
            'year' => 2026,
            'month' => 6,
            'title' => 'SPP Juni 2026',
            'issue_date' => '2026-06-01',
            'due_date' => '2026-06-30',
            'original_amount' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'paid_amount' => 100000,
            'remaining_amount' => 0,
            'status' => 'Lunas',
        ]);
        Bill::create([
            'student_id' => $unpaidStudent->id,
            'academic_year_id' => $oldYear->id,
            'source_type' => 'spp',
            'generation_key' => 'spp-9102-2026-6',
            'year' => 2026,
            'month' => 6,
            'title' => 'SPP Juni 2026',
            'issue_date' => '2026-06-01',
            'due_date' => '2026-06-30',
            'original_amount' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'paid_amount' => 0,
            'remaining_amount' => 100000,
            'status' => 'Belum Bayar',
        ]);

        $this->get('/laporan/spp-perbulan?year=2026&month=6')
            ->assertOk()
            ->assertSee('Siswa Tahun Lama')
            ->assertSee('Rp 100.000')
            ->assertDontSee('Siswa Belum Bayar')
            ->assertDontSee('Belum Bayar');
    }

    public function test_monthly_spp_summary_table_stays_visible_when_empty(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create([
            'nis' => '9103',
            'name' => 'Siswa Kosong SPP',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);

        Bill::create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'source_type' => 'spp',
            'generation_key' => 'spp-9103-2026-7',
            'year' => 2026,
            'month' => 7,
            'title' => 'SPP Juli 2026',
            'issue_date' => '2026-07-01',
            'due_date' => '2026-07-31',
            'original_amount' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'paid_amount' => 0,
            'remaining_amount' => 100000,
            'status' => 'Belum Bayar',
        ]);

        $this->get('/laporan/spp-perbulan?year=2026&month=7')
            ->assertOk()
            ->assertSee('report-summary-table-v2', false)
            ->assertSee('Unit Pendidikan')
            ->assertSee('Lunas')
            ->assertSee('Sebagian')
            ->assertSee('Total Terbayar')
            ->assertDontSee('Total Sisa')
            ->assertDontSee('report-summary-grid-v2', false)
            ->assertDontSee('monthly-spp-chart-card', false)
            ->assertSee('Tanggal')
            ->assertSee('Tahun')
            ->assertSee('Nominal')
            ->assertSee('Cara Bayar')
            ->assertSee('Petugas')
            ->assertSee('Belum ada pembayaran SPP pada periode ini.')
            ->assertDontSee('Siswa Kosong SPP');
    }

    public function test_export_buttons_follow_report_export_permission(): void
    {
        [$year, $unit] = $this->schoolContext();
        $treasurer = User::factory()->create(['role' => 'bendahara']);
        $treasurer->educationUnits()->attach($unit->id);

        $this->actingAs($treasurer)
            ->get('/laporan/transaksi')
            ->assertOk()
            ->assertDontSee('report-export-button', false)
            ->assertDontSee('XLSX')
            ->assertDontSee('PDF');

        $this->get('/laporan/transaksi/export/xlsx')
            ->assertForbidden();
    }

    public function test_yearly_spp_summary_uses_prd_status_counts_and_payment_dates(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        [$year, $unit, $class] = $this->schoolContext();
        $student = Student::create([
            'nis' => '9201',
            'name' => 'Siswa Pertahun',
            'gender' => 'P',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);

        Bill::create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'source_type' => 'spp',
            'generation_key' => 'spp-9201-2025-7',
            'year' => 2025,
            'month' => 7,
            'title' => 'SPP Juli 2025',
            'issue_date' => '2025-07-01',
            'due_date' => '2025-07-31',
            'original_amount' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'paid_amount' => 100000,
            'remaining_amount' => 0,
            'status' => 'Lunas',
        ]);
        Bill::create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'source_type' => 'spp',
            'generation_key' => 'spp-9201-2025-8',
            'year' => 2025,
            'month' => 8,
            'title' => 'SPP Agustus 2025',
            'issue_date' => '2025-08-01',
            'due_date' => '2025-08-31',
            'original_amount' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'paid_amount' => 50000,
            'remaining_amount' => 50000,
            'status' => 'Sebagian',
        ]);
        Bill::create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'source_type' => 'spp',
            'generation_key' => 'spp-9201-2025-9',
            'year' => 2025,
            'month' => 9,
            'title' => 'SPP September 2025',
            'issue_date' => '2025-09-01',
            'due_date' => '2025-09-30',
            'original_amount' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'paid_amount' => 0,
            'remaining_amount' => 100000,
            'status' => 'Belum Bayar',
        ]);
        $payment = SppPayment::create([
            'student_id' => $student->id,
            'transaction_at' => '2025-07-19 09:45:00',
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
            'year' => 2025,
            'month' => 7,
            'original_amount' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'paid_amount' => 100000,
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);

        $this->get('/laporan/spp-tahun-pelajaran?academic_year_id='.$year->id)
            ->assertOk()
            ->assertSee('Jumlah Bulan Lunas')
            ->assertSee('Jumlah Bulan Sebagian')
            ->assertSee('Jumlah Bulan Belum Bayar')
            ->assertDontSee('Total Tagihan SPP')
            ->assertSee('19/07/2025')
            ->assertSee('Sebagian')
            ->assertSee('Rp 150.000')
            ->assertSee('Rp 150.000');
    }

    public function test_unit_recap_footer_and_outstanding_use_full_filtered_result(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);

        foreach (range(1, 11) as $index) {
            $unit = EducationUnit::create(['code' => 'U'.$index, 'name' => 'Unit '.str_pad((string) $index, 2, '0', STR_PAD_LEFT), 'is_active' => true]);
            $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'Kelas '.$index, 'level' => 'Kelas']);
            $student = Student::create([
                'nis' => '93'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'name' => 'Siswa Unit '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'gender' => 'L',
                'school_class_id' => $class->id,
                'academic_year_id' => $year->id,
                'is_active' => true,
            ]);
            $feeType = FeeType::create([
                'education_unit_id' => $unit->id,
                'payment_group' => 'lain-lain',
                'code' => 'LAIN-'.$index,
                'name' => 'Pembayaran Lain '.$index,
                'amount' => 100000,
                'period' => 'Sekali Bayar',
                'is_active' => true,
            ]);
            OtherPayment::create([
                'student_id' => $student->id,
                'fee_type_id' => $feeType->id,
                'transaction_at' => '2026-06-10 08:00:00',
                'payment_method' => 'Cash',
                'status' => 'Diterima',
                'original_amount' => 100000,
                'discount_amount' => 0,
                'total_amount' => 100000,
                'paid_amount' => 100000,
                'remaining_amount' => 0,
                'payment_status' => 'Lunas',
            ]);

            if ($index === 11) {
                Bill::create([
                    'student_id' => $student->id,
                    'academic_year_id' => $year->id,
                    'source_type' => 'spp',
                    'generation_key' => 'spp-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT).'-2026-6',
                    'year' => 2026,
                    'month' => 6,
                    'title' => 'SPP Juni 2026',
                    'issue_date' => '2026-06-01',
                    'due_date' => '2026-06-30',
                    'original_amount' => 70000,
                    'discount_amount' => 0,
                    'total_amount' => 70000,
                    'paid_amount' => 0,
                    'remaining_amount' => 70000,
                    'status' => 'Belum Bayar',
                ]);
            }
        }

        $this->get('/laporan/rekap-unit?date_from=2026-06-01&date_to=2026-06-30&academic_year_id='.$year->id.'&per_page=10')
            ->assertOk()
            ->assertSee('pagination-wrap', false)
            ->assertSeeInOrder(['Total Keseluruhan', 'Rp 1.100.000'])
            ->assertSee('Rp 70.000');
    }

    private function schoolContext(): array
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);

        return [$year, $unit, $class];
    }
}
