<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\EducationUnit;
use App\Models\FeeType;
use App\Models\OtherPayment;
use App\Models\SchoolClass;
use App\Models\SppPayment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Ahmad Fauzan')
            ->assertSee('SMP MaWA');
    }

    public function test_dashboard_handles_empty_financial_data(): void
    {
        $this->actingAs(User::factory()->create())->get('/')
            ->assertOk()
            ->assertSee('Pemasukan Bulan Ini')
            ->assertSee('Belum ada transaksi diterima.');
    }
}
