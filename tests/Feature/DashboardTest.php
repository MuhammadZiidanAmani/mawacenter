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
use App\Models\Student;
use App\Models\User;
use App\Services\DashboardActivityService;
use App\Services\DashboardDataService;
use App\Services\DashboardSummaryService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_important_financial_data(): void
    {
        $this->travelTo('2026-06-14 12:00:00');
        $user = User::factory()->create();
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_active' => true,
        ]);
        $unit = EducationUnit::create(['code' => 'SMP', 'name' => 'SMP MaWA', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => '7', 'is_active' => true]);
        $student = Student::create([
            'nis' => '260001',
            'name' => 'Ahmad Fauzan',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);

        SppPayment::create([
            'student_id' => $student->id,
            'transaction_at' => '2026-06-14 09:00:00',
            'payment_method' => 'Cash',
            'status' => 'Diterima',
            'original_amount' => 300000,
            'discount_amount' => 0,
            'total_amount' => 300000,
            'paid_amount' => 300000,
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);
        OtherPayment::create([
            'student_id' => $student->id,
            'fee_type_id' => FeeType::create([
                'education_unit_id' => $unit->id,
                'code' => 'DAFTAR',
                'name' => 'Daftar Ulang',
                'amount' => 200000,
                'period' => 'Tahunan',
                'is_active' => true,
            ])->id,
            'transaction_at' => '2026-06-13 09:00:00',
            'payment_method' => 'Transfer',
            'status' => 'Diterima',
            'original_amount' => 200000,
            'discount_amount' => 0,
            'total_amount' => 200000,
            'paid_amount' => 200000,
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);
        Bill::create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'source_type' => 'manual',
            'generation_key' => 'dashboard-test-bill',
            'title' => 'Tagihan Kegiatan',
            'issue_date' => '2026-05-01',
            'due_date' => '2026-06-01',
            'original_amount' => 1000000,
            'discount_amount' => 0,
            'total_amount' => 1000000,
            'paid_amount' => 500000,
            'remaining_amount' => 500000,
            'status' => 'Sebagian',
            'unit_name' => $unit->name,
            'class_name' => $class->name,
        ]);

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('Pemasukan Bulan Ini')
            ->assertSee('Rp 500.000')
            ->assertSee('Lewat Jatuh Tempo')
            ->assertSee('Tren Pemasukan')
            ->assertSeeInOrder(['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'])
            ->assertSee('Jun: Rp 500.000')
            ->assertSee('Aktivitas Terbaru')
            ->assertSee('Pembayaran diterima dan perubahan data terbaru')
            ->assertSee('Ahmad Fauzan')
            ->assertSee('SMP MaWA');
    }

    public function test_dashboard_handles_empty_financial_data(): void
    {
        $this->actingAs(User::factory()->create())->get('/')
            ->assertOk()
            ->assertSee('Filter Dashboard')
            ->assertSee('Unit Pendidikan')
            ->assertSee('Tahun Pelajaran')
            ->assertSee('Pemasukan Bulan Ini')
            ->assertSee('Belum ada aktivitas terbaru.');
    }

    public function test_dashboard_filters_financial_data_by_unit_and_academic_year(): void
    {
        $this->travelTo('2026-06-14 12:00:00');
        $activeYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_active' => true,
        ]);
        $archivedYear = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-06-01',
            'end_date' => '2026-05-31',
            'is_active' => false,
        ]);
        $selectedUnit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Pilihan', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'MA', 'name' => 'MA Lain', 'is_active' => true]);
        $selectedClass = SchoolClass::create(['education_unit_id' => $selectedUnit->id, 'name' => 'I A', 'level' => '1', 'is_active' => true]);
        $otherClass = SchoolClass::create(['education_unit_id' => $otherUnit->id, 'name' => 'X A', 'level' => '10', 'is_active' => true]);
        $selectedStudent = Student::create([
            'nis' => 'FILTER-001', 'name' => 'Siswa Tahun Pilihan', 'gender' => 'L',
            'school_class_id' => $selectedClass->id, 'academic_year_id' => $archivedYear->id, 'is_active' => true,
        ]);
        $otherStudent = Student::create([
            'nis' => 'FILTER-002', 'name' => 'Siswa Tahun Aktif', 'gender' => 'P',
            'school_class_id' => $otherClass->id, 'academic_year_id' => $activeYear->id, 'is_active' => true,
        ]);

        foreach ([[$selectedStudent, 222222], [$otherStudent, 999999]] as [$student, $amount]) {
            SppPayment::create([
                'student_id' => $student->id,
                'transaction_at' => '2026-06-14 09:00:00',
                'payment_method' => 'Cash',
                'status' => 'Diterima',
                'original_amount' => $amount,
                'discount_amount' => 0,
                'total_amount' => $amount,
                'paid_amount' => $amount,
                'remaining_amount' => 0,
                'payment_status' => 'Lunas',
            ]);
        }

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/?unit_id='.$selectedUnit->id.'&academic_year_id='.$archivedYear->id)
            ->assertOk()
            ->assertSee('value="'.$selectedUnit->id.'" selected', false)
            ->assertSee('value="'.$archivedYear->id.'" selected', false)
            ->assertSee('Siswa Tahun Pilihan')
            ->assertSee('Rp 222.222')
            ->assertDontSee('Siswa Tahun Aktif')
            ->assertDontSee('Rp 999.999');
    }

    public function test_dashboard_cache_and_financial_data_are_scoped_to_assigned_units(): void
    {
        $this->travelTo('2026-06-14 12:00:00');
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_active' => true,
        ]);
        $assignedUnit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Terakses', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'MA', 'name' => 'MA Rahasia', 'is_active' => true]);
        $assignedClass = SchoolClass::create(['education_unit_id' => $assignedUnit->id, 'name' => 'I A', 'level' => '1', 'is_active' => true]);
        $otherClass = SchoolClass::create(['education_unit_id' => $otherUnit->id, 'name' => 'X A', 'level' => '10', 'is_active' => true]);
        $assignedStudent = Student::create([
            'nis' => 'UNIT-001', 'name' => 'Siswa Terakses', 'gender' => 'L',
            'school_class_id' => $assignedClass->id, 'academic_year_id' => $year->id, 'is_active' => true,
        ]);
        $otherStudent = Student::create([
            'nis' => 'UNIT-002', 'name' => 'Siswa Rahasia', 'gender' => 'P',
            'school_class_id' => $otherClass->id, 'academic_year_id' => $year->id, 'is_active' => true,
        ]);

        foreach ([[$assignedStudent, 111111], [$otherStudent, 999999]] as [$student, $amount]) {
            SppPayment::create([
                'student_id' => $student->id,
                'transaction_at' => '2026-06-14 09:00:00',
                'payment_method' => 'Cash',
                'status' => 'Diterima',
                'original_amount' => $amount,
                'discount_amount' => 0,
                'total_amount' => $amount,
                'paid_amount' => $amount,
                'remaining_amount' => 0,
                'payment_status' => 'Lunas',
            ]);
        }

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/')
            ->assertOk()
            ->assertSee('Siswa Rahasia');

        Role::updateOrCreate(['key' => 'bendahara'], [
            'name' => 'Bendahara Unit',
            'permissions' => ['dashboard.view'],
            'is_active' => true,
        ]);
        $bendahara = User::factory()->create(['role' => 'bendahara']);
        $bendahara->educationUnits()->attach($assignedUnit->id);

        $this->actingAs($bendahara)
            ->get('/')
            ->assertOk()
            ->assertSee('Siswa Terakses')
            ->assertSee('MI Terakses')
            ->assertDontSee('Siswa Rahasia')
            ->assertDontSee('MA Rahasia')
            ->assertSee('Rp 111.111')
            ->assertDontSee('Rp 999.999');

        $this->actingAs($bendahara)
            ->get('/?unit_id='.$otherUnit->id.'&academic_year_id='.$year->id)
            ->assertOk()
            ->assertSee('Siswa Terakses')
            ->assertDontSee('Siswa Rahasia')
            ->assertDontSee('Rp 999.999');
    }

    public function test_dashboard_summary_service_aggregates_only_valid_scoped_financial_data(): void
    {
        $this->travelTo('2026-06-14 12:00:00');
        $activeYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_active' => true,
        ]);
        $archivedYear = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-06-01',
            'end_date' => '2026-05-31',
            'is_active' => false,
        ]);
        $selectedUnit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Terpilih', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'MA', 'name' => 'MA Lain', 'is_active' => true]);
        $selectedClass = SchoolClass::create(['education_unit_id' => $selectedUnit->id, 'name' => 'I A', 'level' => '1', 'is_active' => true]);
        $otherClass = SchoolClass::create(['education_unit_id' => $otherUnit->id, 'name' => 'X A', 'level' => '10', 'is_active' => true]);
        $selectedStudent = Student::create([
            'nis' => 'SUMMARY-001', 'name' => 'Siswa Terpilih', 'gender' => 'L',
            'school_class_id' => $selectedClass->id, 'academic_year_id' => $activeYear->id, 'is_active' => true,
        ]);
        $archivedStudent = Student::create([
            'nis' => 'SUMMARY-002', 'name' => 'Siswa Tahun Lama', 'gender' => 'P',
            'school_class_id' => $selectedClass->id, 'academic_year_id' => $archivedYear->id, 'is_active' => true,
        ]);
        $otherStudent = Student::create([
            'nis' => 'SUMMARY-003', 'name' => 'Siswa Unit Lain', 'gender' => 'P',
            'school_class_id' => $otherClass->id, 'academic_year_id' => $activeYear->id, 'is_active' => true,
        ]);
        $feeType = FeeType::create([
            'education_unit_id' => $selectedUnit->id,
            'code' => 'KEGIATAN',
            'name' => 'Kegiatan',
            'amount' => 200,
            'period' => 'Sekali',
            'is_active' => true,
        ]);

        foreach ([
            [$selectedStudent, '2026-06-14 09:00:00', 'Diterima', 300],
            [$selectedStudent, '2026-05-14 09:00:00', 'Diterima', 250],
            [$selectedStudent, '2026-06-14 10:00:00', 'Pending', 700],
            [$archivedStudent, '2026-06-14 09:00:00', 'Diterima', 888],
            [$otherStudent, '2026-06-14 09:00:00', 'Diterima', 999],
        ] as [$student, $transactionAt, $status, $amount]) {
            SppPayment::create([
                'student_id' => $student->id,
                'transaction_at' => $transactionAt,
                'payment_method' => 'Cash',
                'status' => $status,
                'original_amount' => $amount,
                'discount_amount' => 0,
                'total_amount' => $amount,
                'paid_amount' => $amount,
                'remaining_amount' => 0,
                'payment_status' => 'Lunas',
            ]);
        }

        OtherPayment::create([
            'student_id' => $selectedStudent->id,
            'fee_type_id' => $feeType->id,
            'transaction_at' => '2026-06-13 09:00:00',
            'payment_method' => 'Transfer',
            'status' => 'Diterima',
            'original_amount' => 200,
            'discount_amount' => 0,
            'total_amount' => 200,
            'paid_amount' => 200,
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);

        foreach ([
            ['summary-overdue', '2026-06-01', 1000, 400, 600, 'Sebagian'],
            ['summary-upcoming', '2026-07-01', 500, 100, 400, 'Sebagian'],
            ['summary-cancelled', '2026-05-01', 900, 0, 900, 'Dibatalkan'],
        ] as [$key, $dueDate, $total, $paid, $remaining, $status]) {
            Bill::create([
                'student_id' => $selectedStudent->id,
                'academic_year_id' => $activeYear->id,
                'source_type' => 'manual',
                'generation_key' => $key,
                'title' => 'Tagihan Uji',
                'issue_date' => '2026-05-01',
                'due_date' => $dueDate,
                'original_amount' => $total,
                'discount_amount' => 0,
                'total_amount' => $total,
                'paid_amount' => $paid,
                'remaining_amount' => $remaining,
                'status' => $status,
                'unit_name' => $selectedUnit->name,
                'class_name' => $selectedClass->name,
            ]);
        }

        $service = app(DashboardSummaryService::class);

        $this->assertSame([
            'income_month' => 500,
            'income_today' => 300,
            'income_trend' => 100,
            'outstanding' => 1000,
            'overdue_amount' => 600,
            'overdue_count' => 1,
            'active_students' => 1,
            'collection_rate' => 33,
            'total_billed' => 1500,
            'total_paid' => 500,
        ], $service->summary($activeYear, CarbonImmutable::now(), [$selectedUnit->id]));

        $this->assertSame([
            ['label' => 'Jan', 'amount' => 0],
            ['label' => 'Feb', 'amount' => 0],
            ['label' => 'Mar', 'amount' => 0],
            ['label' => 'Apr', 'amount' => 0],
            ['label' => 'Mei', 'amount' => 250],
            ['label' => 'Jun', 'amount' => 500],
        ], $service->monthlyTrend(CarbonImmutable::now(), $activeYear, [$selectedUnit->id])->all());

        $this->assertSame([
            'income_month' => 0,
            'income_today' => 0,
            'income_trend' => 0,
            'outstanding' => 0,
            'overdue_amount' => 0,
            'overdue_count' => 0,
            'active_students' => 0,
            'collection_rate' => 0,
            'total_billed' => 0,
            'total_paid' => 0,
        ], $service->summary($activeYear, CarbonImmutable::now(), null, []));
    }

    public function test_dashboard_activity_service_combines_scoped_payments_and_data_changes(): void
    {
        $this->travelTo('2026-06-14 12:00:00');
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_active' => true,
        ]);
        $selectedUnit = EducationUnit::create(['code' => 'RAA', 'name' => 'RA Akses', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'RAB', 'name' => 'RA Lain', 'is_active' => true]);
        $selectedClass = SchoolClass::create(['education_unit_id' => $selectedUnit->id, 'name' => 'A1', 'level' => 'A', 'is_active' => true]);
        $otherClass = SchoolClass::create(['education_unit_id' => $otherUnit->id, 'name' => 'B1', 'level' => 'B', 'is_active' => true]);
        $selectedStudent = Student::create([
            'nis' => 'ACTIVITY-001', 'name' => 'Siswa Aktivitas', 'gender' => 'L',
            'school_class_id' => $selectedClass->id, 'academic_year_id' => $year->id, 'is_active' => true,
        ]);
        $otherStudent = Student::create([
            'nis' => 'ACTIVITY-002', 'name' => 'Siswa Tidak Terakses', 'gender' => 'P',
            'school_class_id' => $otherClass->id, 'academic_year_id' => $year->id, 'is_active' => true,
        ]);

        $payments = collect([
            [$selectedStudent, '2026-06-14 09:00:00', 150000],
            [$otherStudent, '2026-06-14 11:00:00', 990000],
        ])->map(fn (array $data) => SppPayment::create([
            'student_id' => $data[0]->id,
            'transaction_at' => $data[1],
            'payment_method' => 'Cash',
            'status' => 'Diterima',
            'original_amount' => $data[2],
            'discount_amount' => 0,
            'total_amount' => $data[2],
            'paid_amount' => $data[2],
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]));

        foreach ($payments as $index => $payment) {
            AuditLog::create([
                'action' => 'payments.spp.correct',
                'subject_type' => SppPayment::class,
                'subject_id' => $payment->id,
                'student_count' => 1,
                'student_ids' => [$payment->student_id],
                'metadata' => ['reason' => $index === 0 ? 'Koreksi terakses' : 'Koreksi unit lain'],
            ]);
        }

        $activities = app(DashboardActivityService::class)
            ->recent($year, [$selectedUnit->id]);

        $this->assertSame(['change', 'payment'], $activities->pluck('kind')->all());
        $this->assertSame(['Koreksi SPP', 'SPP'], $activities->pluck('type')->all());
        $this->assertSame(['Siswa Aktivitas'], $activities->pluck('student')->unique()->values()->all());
        $this->assertSame([null, 150000], $activities->pluck('amount')->all());
        $this->assertTrue(app(DashboardActivityService::class)->recent($year, [])->isEmpty());
    }

    public function test_dashboard_data_service_provides_the_complete_cacheable_view_contract(): void
    {
        $data = app(DashboardDataService::class)->viewData(
            null,
            CarbonImmutable::parse('2026-06-14 12:00:00'),
            [],
        );

        $this->assertSame(['stats', 'monthlyTrend', 'recentActivities', 'unitSummaries'], array_keys($data));
        $this->assertCount(10, $data['stats']);
        $this->assertCount(6, $data['monthlyTrend']);
        $this->assertSame([], $data['recentActivities']);
        $this->assertSame([], $data['unitSummaries']);
        $this->assertJson(json_encode($data, JSON_THROW_ON_ERROR));
    }

    public function test_dashboard_does_not_treat_an_empty_unit_scope_as_global_access(): void
    {
        $this->travelTo('2026-06-14 12:00:00');
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_active' => true,
        ]);
        $unit = EducationUnit::create(['code' => 'RA', 'name' => 'RA Terbatas', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'A', 'level' => 'A', 'is_active' => true]);
        $student = Student::create([
            'nis' => 'SCOPE-001',
            'name' => 'Siswa Rahasia',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        SppPayment::create([
            'student_id' => $student->id,
            'transaction_at' => '2026-06-14 09:00:00',
            'payment_method' => 'Cash',
            'status' => 'Diterima',
            'original_amount' => 321000,
            'discount_amount' => 0,
            'total_amount' => 321000,
            'paid_amount' => 321000,
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);
        Bill::create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'source_type' => 'manual',
            'generation_key' => 'dashboard-unit-scope',
            'title' => 'Tagihan Terbatas',
            'issue_date' => '2026-06-01',
            'due_date' => '2026-06-10',
            'original_amount' => 500000,
            'discount_amount' => 0,
            'total_amount' => 500000,
            'paid_amount' => 0,
            'remaining_amount' => 500000,
            'status' => 'Belum Lunas',
            'unit_name' => $unit->name,
            'class_name' => $class->name,
        ]);
        Role::create([
            'key' => 'dashboard_unit_viewer',
            'name' => 'Pemantau Dashboard Unit',
            'permissions' => ['dashboard.view'],
            'is_active' => true,
        ]);
        $viewer = User::factory()->create(['role' => 'dashboard_unit_viewer']);

        $this->actingAs($viewer)
            ->get('/?unit_id='.$unit->id)
            ->assertOk()
            ->assertViewHas('educationUnits', fn ($units) => $units->isEmpty())
            ->assertViewHas('stats', fn (array $stats) => $stats['income_month'] === 0
                && $stats['outstanding'] === 0
                && $stats['active_students'] === 0)
            ->assertViewHas('monthlyTrend', fn ($trend) => $trend->every(fn (array $month) => $month['amount'] === 0))
            ->assertViewHas('recentActivities', fn ($activities) => $activities->isEmpty())
            ->assertViewHas('unitSummaries', fn ($units) => $units->isEmpty())
            ->assertDontSee('Siswa Rahasia')
            ->assertDontSee('RA Terbatas');

        $viewer->educationUnits()->attach($unit->id);

        $this->actingAs($viewer)
            ->get('/')
            ->assertOk()
            ->assertViewHas('stats', fn (array $stats) => $stats['income_month'] === 321000
                && $stats['outstanding'] === 500000
                && $stats['active_students'] === 1)
            ->assertViewHas('recentActivities', fn ($activities) => $activities->pluck('student')->contains('Siswa Rahasia'))
            ->assertViewHas('unitSummaries', fn ($units) => $units->pluck('name')->contains('RA Terbatas'))
            ->assertSee('Siswa Rahasia')
            ->assertSee('RA Terbatas');
    }

    public function test_dashboard_controller_passes_summary_service_result_to_the_view(): void
    {
        config(['performance.query_cache.enabled' => false]);
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_active' => true,
        ]);
        $expectedStats = [
            'income_month' => 123000,
            'income_today' => 23000,
            'income_trend' => 12,
            'outstanding' => 456000,
            'overdue_amount' => 78000,
            'overdue_count' => 2,
            'active_students' => 34,
            'collection_rate' => 67,
            'total_billed' => 1000000,
            'total_paid' => 670000,
        ];
        $summaryService = Mockery::mock(DashboardSummaryService::class);
        $summaryService->shouldReceive('summary')
            ->once()
            ->with(
                Mockery::on(fn (?AcademicYear $selectedYear) => $selectedYear?->is($year)),
                Mockery::type(CarbonImmutable::class),
                null,
                null,
            )
            ->andReturn($expectedStats);
        $summaryService->shouldReceive('monthlyTrend')
            ->once()
            ->with(
                Mockery::type(CarbonImmutable::class),
                Mockery::on(fn (?AcademicYear $selectedYear) => $selectedYear?->is($year)),
                null,
                null,
            )
            ->andReturn(collect([
                ['label' => 'Jan', 'amount' => 0],
                ['label' => 'Feb', 'amount' => 0],
                ['label' => 'Mar', 'amount' => 0],
                ['label' => 'Apr', 'amount' => 0],
                ['label' => 'Mei', 'amount' => 0],
                ['label' => 'Jun', 'amount' => 123000],
            ]));
        $this->app->instance(DashboardSummaryService::class, $summaryService);

        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertViewHas('stats', $expectedStats)
            ->assertSee('Rp 123.000')
            ->assertSee('Rp 456.000')
            ->assertSee('34')
            ->assertSee('67%');
    }

    public function test_dashboard_filter_options_use_active_units_and_default_to_the_active_academic_year(): void
    {
        $activeYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_active' => true,
        ]);
        $archivedYear = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-06-01',
            'end_date' => '2026-05-31',
            'is_active' => false,
        ]);
        $activeUnit = EducationUnit::create(['code' => 'MTSA', 'name' => 'MTs Aktif', 'is_active' => true]);
        $inactiveUnit = EducationUnit::create(['code' => 'MTSN', 'name' => 'MTs Nonaktif', 'is_active' => false]);

        $this->actingAs(User::factory()->create())
            ->get('/?unit_id='.$inactiveUnit->id.'&academic_year_id=999999')
            ->assertOk()
            ->assertViewHas('educationUnits', fn ($units) => $units->modelKeys() === [$activeUnit->id])
            ->assertViewHas('academicYears', fn ($years) => $years->modelKeys() === [$activeYear->id, $archivedYear->id])
            ->assertViewHas('selectedUnitId', null)
            ->assertViewHas('selectedAcademicYear', fn (?AcademicYear $year) => $year?->is($activeYear))
            ->assertSee('MTs Aktif')
            ->assertDontSee('MTs Nonaktif')
            ->assertSee('2026/2027 (Aktif)')
            ->assertSee('value="'.$activeYear->id.'" selected', false);
    }

    public function test_dashboard_rejects_malformed_filter_values_with_indonesian_messages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/')
            ->get('/?unit_id=bukan-angka&academic_year_id=-1')
            ->assertRedirect('/')
            ->assertSessionHasErrors([
                'unit_id' => 'Unit pendidikan yang dipilih tidak valid.',
                'academic_year_id' => 'Tahun pelajaran yang dipilih tidak valid.',
            ]);
    }

    public function test_sidebar_groups_follow_the_operational_menu_order(): void
    {
        $this->actingAs(User::factory()->create())->get('/')
            ->assertOk()
            ->assertSeeInOrder([
                'UTAMA',
                'KEUANGAN',
                'LAPORAN',
                'MANAJEMEN SISWA',
                'MASTER DATA',
            ]);
    }

    public function test_report_links_are_flat_and_account_menu_contains_the_available_actions(): void
    {
        $this->actingAs(User::factory()->create())->get('/')
            ->assertOk()
            ->assertSeeInOrder([
                'LAPORAN',
                'Transaksi Pembayaran',
                'SPP Perbulan',
                'SPP Pertahun',
                'Rekap Per Unit',
            ])
            ->assertDontSee('data-nav-toggle', false)
            ->assertDontSee('PENGATURAN')
            ->assertSee('href="'.route('settings.index').'"', false)
            ->assertSee('data-account-menu', false)
            ->assertSee('data-account-menu-toggle', false)
            ->assertSee('aria-label="Menu akun"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('role="menu"', false)
            ->assertSee('<circle cx="12" cy="8" r="4"/>', false)
            ->assertSee('Pengaturan Akun')
            ->assertSee('aria-label="Keluar"', false);

        Role::create([
            'key' => 'dashboard_viewer',
            'name' => 'Dashboard Viewer',
            'permissions' => ['dashboard.view'],
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'dashboard_viewer']))->get('/')
            ->assertOk()
            ->assertSee('data-account-menu', false)
            ->assertDontSee('Pengaturan Akun')
            ->assertSee('aria-label="Keluar"', false);
    }
}
