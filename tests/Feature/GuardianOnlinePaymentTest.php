<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\EducationUnit;
use App\Models\OnlinePaymentTransaction;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GuardianOnlinePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.duitku.enabled' => true,
            'services.duitku.merchant_code' => 'DTEST',
            'services.duitku.api_key' => 'test-api-key',
            'services.duitku.base_url' => 'https://sandbox.duitku.test',
            'services.duitku.callback_url' => 'https://mawa.test/duitku/callback',
            'services.duitku.return_url' => 'https://mawa.test/wali-santri/tagihan',
            'services.duitku.expiry_minutes' => 60,
        ]);

        Http::preventStrayRequests();
        $this->seedGuardianRole();
    }

    public function test_guardian_can_create_duitku_transaction_with_provider_service_fee(): void
    {
        [$guardian, $student, $bill] = $this->guardianBill();

        Http::fake([
            'https://sandbox.duitku.test/webapi/api/merchant/paymentmethod/getpaymentmethod' => Http::response([
                'paymentFee' => [[
                    'paymentMethod' => 'I1',
                    'paymentName' => 'BNI Virtual Account',
                    'paymentImage' => 'https://images.test/bni.png',
                    'totalFee' => '2500',
                ]],
                'responseCode' => '00',
                'responseMessage' => 'SUCCESS',
            ]),
            'https://sandbox.duitku.test/webapi/api/merchant/v2/inquiry' => Http::response([
                'merchantCode' => 'DTEST',
                'reference' => 'DTEST-REFERENCE-001',
                'paymentUrl' => 'https://sandbox.duitku.test/pay/reference-001',
                'vaNumber' => '988880000001',
                'amount' => '125000',
                'statusCode' => '00',
                'statusMessage' => 'SUCCESS',
            ]),
        ]);

        $response = $this->actingAs($guardian)
            ->postJson(route('guardian.online-payments.store'), [
                'student_id' => $student->id,
                'bill_ids' => [$bill->id],
                'payment_method' => 'I1',
            ]);

        $response->assertCreated()
            ->assertJsonPath('transaction.payment_method', 'I1')
            ->assertJsonPath('transaction.payment_method_name', 'BNI Virtual Account')
            ->assertJsonPath('transaction.amount', 125000)
            ->assertJsonPath('transaction.service_fee', 2500)
            ->assertJsonPath('transaction.total_amount', 127500)
            ->assertJsonPath('transaction.status', OnlinePaymentTransaction::STATUS_PENDING)
            ->assertJsonPath('transaction.reference', 'DTEST-REFERENCE-001')
            ->assertJsonPath('transaction.payment_url', 'https://sandbox.duitku.test/pay/reference-001');

        $transaction = OnlinePaymentTransaction::firstOrFail();
        $this->assertSame([$bill->id], $transaction->bill_ids);
        $this->assertSame(125000, $transaction->amount);
        $this->assertSame(2500, $transaction->service_fee);
        $this->assertSame(127500, $transaction->total_amount);

        $this->assertDatabaseHas('bills', [
            'id' => $bill->id,
            'paid_amount' => 0,
            'remaining_amount' => 125000,
            'status' => 'Belum Dibayar',
        ]);
        $this->assertSame(0, DB::table('bill_payment_allocations')->count());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payments.online.create',
            'subject_type' => OnlinePaymentTransaction::class,
            'subject_id' => $transaction->id,
            'student_count' => 1,
        ]);

        Http::assertSent(function (Request $request) {
            if (! str_ends_with($request->url(), '/paymentmethod/getpaymentmethod')) {
                return false;
            }

            $datetime = $request['datetime'];
            $expectedSignature = hash_hmac('sha256', 'DTEST125000'.$datetime, 'test-api-key');

            return $request['merchantcode'] === 'DTEST'
                && $request['amount'] === 125000
                && hash_equals($expectedSignature, $request['signature']);
        });
        Http::assertSent(function (Request $request) use ($transaction) {
            if (! str_ends_with($request->url(), '/merchant/v2/inquiry')) {
                return false;
            }

            $expectedSignature = hash_hmac(
                'sha256',
                'DTEST'.$transaction->merchant_order_id.'125000',
                'test-api-key',
            );

            return $request['paymentAmount'] === 125000
                && $request['paymentMethod'] === 'I1'
                && $request['itemDetails'] === [[
                    'name' => 'SPP November 2026',
                    'price' => 125000,
                    'quantity' => 1,
                ]]
                && $request['callbackUrl'] === 'https://mawa.test/duitku/callback'
                && hash_equals($expectedSignature, $request['signature']);
        });
    }

    public function test_guardian_cannot_create_transaction_for_an_unlinked_student(): void
    {
        [$guardian] = $this->guardianBill();
        [, $outsideStudent, $outsideBill] = $this->guardianBill('399002', 'Siswa di Luar Akses', false);

        $this->actingAs($guardian)
            ->postJson(route('guardian.online-payments.store'), [
                'student_id' => $outsideStudent->id,
                'bill_ids' => [$outsideBill->id],
                'payment_method' => 'I1',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('student_id');

        $this->assertDatabaseCount('online_payment_transactions', 0);
        Http::assertNothingSent();
    }

    public function test_failed_duitku_inquiry_never_reduces_the_bill(): void
    {
        [$guardian, $student, $bill] = $this->guardianBill();

        Http::fake([
            'https://sandbox.duitku.test/webapi/api/merchant/paymentmethod/getpaymentmethod' => Http::response([
                'paymentFee' => [[
                    'paymentMethod' => 'I1',
                    'paymentName' => 'BNI Virtual Account',
                    'totalFee' => '2500',
                ]],
                'responseCode' => '00',
            ]),
            'https://sandbox.duitku.test/webapi/api/merchant/v2/inquiry' => Http::response([
                'Message' => 'Temporary gateway error',
            ], 500),
        ]);

        $this->actingAs($guardian)
            ->postJson(route('guardian.online-payments.store'), [
                'student_id' => $student->id,
                'bill_ids' => [$bill->id],
                'payment_method' => 'I1',
            ])
            ->assertStatus(502)
            ->assertJsonPath('message', 'Transaksi belum dapat dibuat. Silakan coba kembali.');

        $this->assertDatabaseHas('online_payment_transactions', [
            'student_id' => $student->id,
            'status' => OnlinePaymentTransaction::STATUS_FAILED,
            'amount' => 125000,
            'service_fee' => 2500,
        ]);
        $this->assertDatabaseHas('bills', [
            'id' => $bill->id,
            'paid_amount' => 0,
            'remaining_amount' => 125000,
            'status' => 'Belum Dibayar',
        ]);
        $this->assertSame(0, DB::table('bill_payment_allocations')->count());
    }

    public function test_valid_callback_allocates_bill_once_even_when_resent(): void
    {
        [$guardian, $student, $bill] = $this->guardianBill();
        $transaction = $this->pendingTransaction($guardian, $student, $bill);
        $payload = $this->callbackPayload($transaction, '00');

        $this->post(route('duitku.callback'), $payload)
            ->assertOk()
            ->assertContent('OK');

        $this->assertDatabaseHas('online_payment_transactions', [
            'id' => $transaction->id,
            'status' => OnlinePaymentTransaction::STATUS_ACCEPTED,
            'result_code' => '00',
            'allocated_amount' => 125000,
            'reconciliation_status' => null,
        ]);
        $this->assertDatabaseHas('bill_payment_allocations', [
            'bill_id' => $bill->id,
            'payment_type' => 'online_payment',
            'payment_id' => $transaction->id,
            'amount' => 125000,
        ]);
        $this->assertDatabaseHas('bills', [
            'id' => $bill->id,
            'paid_amount' => 125000,
            'remaining_amount' => 0,
            'status' => 'Lunas',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payments.online.accept',
            'subject_type' => OnlinePaymentTransaction::class,
            'subject_id' => $transaction->id,
            'student_count' => 1,
        ]);

        $this->post(route('duitku.callback'), $payload)
            ->assertOk()
            ->assertContent('OK');

        $this->assertSame(1, DB::table('bill_payment_allocations')
            ->where('payment_type', 'online_payment')
            ->where('payment_id', $transaction->id)
            ->count());
        $this->assertSame(1, DB::table('audit_logs')
            ->where('action', 'payments.online.accept')
            ->where('subject_type', OnlinePaymentTransaction::class)
            ->where('subject_id', $transaction->id)
            ->count());
    }

    public function test_callback_with_invalid_signature_is_rejected_without_mutation(): void
    {
        [$guardian, $student, $bill] = $this->guardianBill();
        $transaction = $this->pendingTransaction($guardian, $student, $bill);
        $payload = $this->callbackPayload($transaction, '00');
        $payload['signature'] = str_repeat('0', 64);

        $this->post(route('duitku.callback'), $payload)->assertUnauthorized();

        $this->assertDatabaseHas('online_payment_transactions', [
            'id' => $transaction->id,
            'status' => OnlinePaymentTransaction::STATUS_PENDING,
            'allocated_amount' => 0,
        ]);
        $this->assertDatabaseHas('bills', [
            'id' => $bill->id,
            'remaining_amount' => 125000,
            'status' => 'Belum Dibayar',
        ]);
        $this->assertDatabaseCount('bill_payment_allocations', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_failed_callback_is_recorded_idempotently_without_bill_allocation(): void
    {
        [$guardian, $student, $bill] = $this->guardianBill();
        $transaction = $this->pendingTransaction($guardian, $student, $bill);
        $payload = $this->callbackPayload($transaction, '01');

        $this->post(route('duitku.callback'), $payload)->assertOk();
        $this->post(route('duitku.callback'), $payload)->assertOk();

        $this->assertDatabaseHas('online_payment_transactions', [
            'id' => $transaction->id,
            'status' => OnlinePaymentTransaction::STATUS_FAILED,
            'result_code' => '01',
            'allocated_amount' => 0,
        ]);
        $this->assertDatabaseHas('bills', [
            'id' => $bill->id,
            'remaining_amount' => 125000,
            'status' => 'Belum Dibayar',
        ]);
        $this->assertDatabaseCount('bill_payment_allocations', 0);
        $this->assertSame(1, DB::table('audit_logs')
            ->where('action', 'payments.online.fail')
            ->where('subject_type', OnlinePaymentTransaction::class)
            ->where('subject_id', $transaction->id)
            ->count());
    }

    private function seedGuardianRole(): void
    {
        Role::updateOrCreate(
            ['key' => 'orang_tua'],
            [
                'name' => Role::DEFAULTS['orang_tua'],
                'permissions' => Role::defaultPermissionsFor('orang_tua'),
                'is_active' => true,
            ],
        );
    }

    private function guardianBill(
        string $nis = '399001',
        string $studentName = 'Siswa Pembayaran Online',
        bool $attach = true,
    ): array {
        $year = AcademicYear::firstOrCreate(
            ['name' => '2026/2027'],
            ['is_active' => true],
        );
        $unit = EducationUnit::firstOrCreate(
            ['code' => 'MI'],
            ['name' => 'MI Mambaul Hikmah', 'is_active' => true],
        );
        $class = SchoolClass::firstOrCreate(
            ['education_unit_id' => $unit->id, 'name' => '6A'],
            ['level' => 6, 'is_active' => true],
        );
        $student = Student::create([
            'nis' => $nis,
            'name' => $studentName,
            'gender' => 'L',
            'guardian_name' => 'Wali Santri',
            'whatsapp' => '081234567890',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'entry_date' => '2025-07-01',
            'is_active' => true,
        ]);
        $bill = Bill::create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'source_type' => 'spp',
            'year' => 2026,
            'month' => 11,
            'generation_key' => 'online-test-'.$nis,
            'title' => 'SPP November 2026',
            'issue_date' => '2026-11-01',
            'due_date' => '2026-11-10',
            'original_amount' => 125000,
            'total_amount' => 125000,
            'paid_amount' => 0,
            'remaining_amount' => 125000,
            'status' => 'Belum Dibayar',
        ]);
        $guardian = User::factory()->create(['role' => 'orang_tua']);

        if ($attach) {
            $guardian->guardianStudents()->attach($student->id);
        }

        return [$guardian, $student, $bill];
    }

    private function pendingTransaction(User $guardian, Student $student, Bill $bill): OnlinePaymentTransaction
    {
        return OnlinePaymentTransaction::create([
            'user_id' => $guardian->id,
            'student_id' => $student->id,
            'merchant_order_id' => 'MWA-CALLBACK-'.$bill->id,
            'bill_ids' => [$bill->id],
            'payment_method' => 'I1',
            'payment_method_name' => 'BNI Virtual Account',
            'amount' => 125000,
            'service_fee' => 2500,
            'total_amount' => 127500,
            'status' => OnlinePaymentTransaction::STATUS_PENDING,
            'provider_reference' => 'DTEST-CALLBACK-'.$bill->id,
            'payment_url' => 'https://sandbox.duitku.test/pay/callback-'.$bill->id,
            'expires_at' => now()->addHour(),
        ]);
    }

    private function callbackPayload(OnlinePaymentTransaction $transaction, string $resultCode): array
    {
        $payload = [
            'merchantCode' => 'DTEST',
            'amount' => (string) $transaction->amount,
            'merchantOrderId' => $transaction->merchant_order_id,
            'paymentCode' => $transaction->payment_method,
            'resultCode' => $resultCode,
            'reference' => $transaction->provider_reference,
            'publisherOrderId' => 'PUBLISHER-'.$transaction->id,
            'settlementDate' => now()->toDateString(),
        ];
        $payload['signature'] = hash_hmac(
            'sha256',
            $payload['merchantCode'].$payload['amount'].$payload['merchantOrderId'],
            'test-api-key',
        );

        return $payload;
    }
}
