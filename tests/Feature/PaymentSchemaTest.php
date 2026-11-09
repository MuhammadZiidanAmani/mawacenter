<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\BillPaymentAllocation;
use App\Models\EducationUnit;
use App\Models\FeeType;
use App\Models\OtherPayment;
use App\Models\SchoolClass;
use App\Models\SppPayment;
use App\Models\Student;
use App\Services\BillService;
use App\Services\ChargeCalculator;
use App\Services\OtherPaymentService;
use App\Services\PaymentReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PaymentSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_payment_and_bill_allocation_schema_has_the_required_contract(): void
    {
        $this->assertTrue(Schema::hasColumns('spp_payments', [
            'student_id', 'transaction_at', 'payment_method', 'status', 'total_amount',
            'paid_amount', 'remaining_amount', 'payment_status', 'operator_user_id',
        ]));
        $this->assertTrue(Schema::hasColumns('other_payments', [
            'student_id', 'fee_type_id', 'transaction_at', 'payment_method', 'status',
            'total_amount', 'paid_amount', 'remaining_amount', 'payment_status', 'operator_user_id',
        ]));
        $this->assertTrue(Schema::hasColumns('bill_payment_allocations', [
            'bill_id', 'payment_type', 'payment_id', 'amount',
        ]));

        $this->assertContains('spp_payments_student_status_date_index', collect(Schema::getIndexes('spp_payments'))->pluck('name'));
        $this->assertContains('other_payments_student_status_date_index', collect(Schema::getIndexes('other_payments'))->pluck('name'));
        $this->assertContains('bill_allocations_bill_created_index', collect(Schema::getIndexes('bill_payment_allocations'))->pluck('name'));
        $this->assertSame([
            'spp', 'other', 'manual', 'guardian_transfer', 'online_payment',
        ], BillPaymentAllocation::TYPES);
    }

    public function test_spp_payment_exposes_only_its_own_bill_allocations(): void
    {
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_active' => true,
        ]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Schema', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'I Schema', 'level' => '1', 'is_active' => true]);
        $student = Student::create([
            'nis' => 'SCHEMA-001', 'name' => 'Siswa Schema', 'gender' => 'L',
            'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true,
        ]);
        $payment = SppPayment::create([
            'student_id' => $student->id,
            'transaction_at' => '2026-11-09 09:00:00',
            'payment_method' => 'Cash',
            'status' => 'Diterima',
            'original_amount' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'paid_amount' => 100000,
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);
        $bill = Bill::create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'source_type' => 'spp',
            'year' => 2026,
            'month' => 11,
            'generation_key' => 'payment-schema-allocation',
            'title' => 'SPP November',
            'issue_date' => '2026-11-01',
            'due_date' => '2026-11-10',
            'original_amount' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'paid_amount' => 100000,
            'remaining_amount' => 0,
            'status' => 'Lunas',
        ]);
        $bill->allocations()->create([
            'payment_type' => BillPaymentAllocation::TYPE_SPP,
            'payment_id' => $payment->id,
            'amount' => 100000,
        ]);
        $bill->allocations()->create([
            'payment_type' => BillPaymentAllocation::TYPE_OTHER,
            'payment_id' => $payment->id,
            'amount' => 50000,
        ]);

        $this->assertCount(1, $payment->billAllocations);
        $this->assertTrue($payment->billAllocations->first()->bill->is($bill));
        $this->assertSame(100000, $payment->billAllocations->first()->amount);
    }

    public function test_receipt_service_generates_stable_numbers_for_every_cashier_payment_group(): void
    {
        $receipts = app(PaymentReceiptService::class);
        $spp = new SppPayment(['transaction_at' => '2026-11-09 09:00:00']);
        $spp->id = 17;

        $this->assertSame('SPP-20261109-000017', $receipts->number($spp));

        foreach ([
            'pendaftaran' => 'PD',
            'daftar-ulang' => 'DU',
            'laundry' => 'LD',
            'lain-lain' => 'LL',
        ] as $group => $prefix) {
            $payment = new OtherPayment(['transaction_at' => '2026-11-09 10:00:00']);
            $payment->id = 42;
            $payment->setRelation('feeType', new FeeType(['payment_group' => $group]));

            $this->assertSame($prefix.'-20261109-000042', $receipts->number($payment));
        }
    }

    public function test_other_payment_record_rolls_back_when_bill_allocation_sync_fails(): void
    {
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_active' => true,
        ]);
        $unit = EducationUnit::create(['code' => 'RA', 'name' => 'RA Atomik', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'A Atomik', 'level' => 'A', 'is_active' => true]);
        $student = Student::create([
            'nis' => 'ATOMIC-OTHER-001', 'name' => 'Siswa Atomik', 'gender' => 'P',
            'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true,
        ]);
        $feeType = FeeType::create([
            'education_unit_id' => $unit->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'lain-lain',
            'code' => 'OTHER-ATOMIC-SCHEMA',
            'name' => 'Pembayaran Atomik',
            'amount' => 125000,
            'period' => 'Sekali Bayar',
            'creates_bill' => false,
            'is_active' => true,
        ]);
        $bills = Mockery::mock(BillService::class);
        $bills->shouldReceive('syncOtherPayment')
            ->once()
            ->andThrow(new RuntimeException('Simulasi sinkronisasi alokasi gagal.'));
        $service = new OtherPaymentService(app(ChargeCalculator::class), $bills);

        try {
            $service->record($student, $feeType, [
                'transaction_date' => '2026-11-09',
                'transaction_time' => '10:00:00',
                'payment_method' => 'Cash',
                'status' => 'Diterima',
                'paid_amount' => 125000,
            ]);
            $this->fail('Pencatatan pembayaran seharusnya ikut dibatalkan saat sinkronisasi gagal.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi sinkronisasi alokasi gagal.', $exception->getMessage());
        }

        $this->assertDatabaseCount('other_payments', 0);
        $this->assertDatabaseCount('bill_payment_allocations', 0);
    }
}
