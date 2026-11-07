<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\OnlinePaymentTransaction;
use App\Services\AuditLogService;
use App\Support\PerformanceCache;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class DuitkuWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless(config('services.duitku.enabled'), 404);

        $payload = $this->validatedPayload($request);
        $this->verifySignature($payload);

        $transaction = OnlinePaymentTransaction::query()
            ->where('merchant_order_id', $payload['merchantOrderId'])
            ->firstOrFail();

        abort_unless(hash_equals((string) config('services.duitku.merchant_code'), $payload['merchantCode']), 401);
        abort_unless((int) $transaction->amount === $payload['amount'], 422, 'Nominal callback tidak sesuai.');
        abort_unless(
            blank($transaction->provider_reference)
                || hash_equals((string) $transaction->provider_reference, $payload['reference']),
            422,
            'Referensi callback tidak sesuai.',
        );

        $processed = DB::transaction(function () use ($payload, $request) {
            $transaction = OnlinePaymentTransaction::query()
                ->where('merchant_order_id', $payload['merchantOrderId'])
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $transaction->status === OnlinePaymentTransaction::STATUS_ACCEPTED
                || (
                    $transaction->status === OnlinePaymentTransaction::STATUS_FAILED
                    && $transaction->callback_received_at
                    && $transaction->result_code === $payload['resultCode']
                )
            ) {
                return null;
            }

            if ($payload['resultCode'] !== '00') {
                $transaction->update([
                    'status' => OnlinePaymentTransaction::STATUS_FAILED,
                    'result_code' => $payload['resultCode'],
                    'provider_reference' => $transaction->provider_reference ?: $payload['reference'],
                    'callback_payload' => $this->storablePayload($payload),
                    'callback_received_at' => now(),
                    'failure_reason' => 'Pembayaran dinyatakan gagal oleh Duitku.',
                ]);

                $transaction = $transaction->refresh();
                $this->recordAudit($request, $transaction);

                return $transaction;
            }

            $remainingPayment = (int) $transaction->amount;
            $allocatedAmount = 0;
            $bills = Bill::query()
                ->whereIn('id', $transaction->bill_ids ?? [])
                ->where('student_id', $transaction->student_id)
                ->where('status', '!=', 'Dibatalkan')
                ->orderByRaw("CASE WHEN source_type = 'spp' THEN 0 ELSE 1 END")
                ->orderBy('year')
                ->orderBy('month')
                ->lockForUpdate()
                ->get();

            foreach ($bills as $bill) {
                if ($remainingPayment < 1 || (int) $bill->remaining_amount < 1) {
                    continue;
                }

                $allocated = min($remainingPayment, (int) $bill->remaining_amount);
                $bill->allocations()->updateOrCreate(
                    ['payment_type' => 'online_payment', 'payment_id' => $transaction->id],
                    ['amount' => $allocated],
                );
                $remainingPayment -= $allocated;
                $allocatedAmount += $allocated;
                $this->refreshBill($bill);
            }

            $transaction->update([
                'status' => OnlinePaymentTransaction::STATUS_ACCEPTED,
                'result_code' => $payload['resultCode'],
                'allocated_amount' => $allocatedAmount,
                'reconciliation_status' => $allocatedAmount === (int) $transaction->amount ? null : 'Selisih Alokasi',
                'provider_reference' => $transaction->provider_reference ?: $payload['reference'],
                'callback_payload' => $this->storablePayload($payload),
                'callback_received_at' => now(),
                'paid_at' => now(),
                'failure_reason' => null,
            ]);

            $transaction = $transaction->refresh();
            $this->recordAudit($request, $transaction);

            return $transaction;
        });

        if ($processed) {
            PerformanceCache::invalidate(['dashboard', 'bills', 'reports']);
        }

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    private function validatedPayload(Request $request): array
    {
        $payload = [
            'merchantCode' => trim((string) $request->input('merchantCode')),
            'amount' => filter_var($request->input('amount'), FILTER_VALIDATE_INT),
            'merchantOrderId' => trim((string) $request->input('merchantOrderId')),
            'paymentCode' => trim((string) $request->input('paymentCode')),
            'resultCode' => trim((string) $request->input('resultCode')),
            'reference' => trim((string) $request->input('reference')),
            'signature' => strtolower(trim((string) $request->input('signature'))),
            'publisherOrderId' => trim((string) $request->input('publisherOrderId')),
            'settlementDate' => trim((string) $request->input('settlementDate')),
            'issuerCode' => trim((string) $request->input('issuerCode')),
            'customerName' => trim((string) $request->input('customerName')),
        ];

        abort_unless(
            $payload['merchantCode'] !== ''
                && is_int($payload['amount'])
                && $payload['amount'] > 0
                && $payload['merchantOrderId'] !== ''
                && strlen($payload['merchantOrderId']) <= 50
                && in_array($payload['resultCode'], ['00', '01'], true)
                && $payload['reference'] !== ''
                && preg_match('/^[a-f0-9]{64}$/', $payload['signature']) === 1,
            422,
            'Parameter callback tidak lengkap atau tidak valid.',
        );

        return $payload;
    }

    private function verifySignature(array $payload): void
    {
        $apiKey = (string) config('services.duitku.api_key');
        abort_unless($apiKey !== '', 503, 'Konfigurasi callback belum lengkap.');

        $expected = hash_hmac(
            'sha256',
            $payload['merchantCode'].$payload['amount'].$payload['merchantOrderId'],
            $apiKey,
        );

        abort_unless(hash_equals($expected, $payload['signature']), 401, 'Signature callback tidak valid.');
    }

    private function storablePayload(array $payload): array
    {
        unset($payload['signature']);

        return $payload;
    }

    private function refreshBill(Bill $bill): void
    {
        $paid = min((int) $bill->total_amount, (int) $bill->allocations()->sum('amount'));
        $remaining = max(0, (int) $bill->total_amount - $paid);
        $bill->update([
            'paid_amount' => $paid,
            'remaining_amount' => $remaining,
            'status' => $remaining === 0 ? 'Lunas' : ($paid > 0 ? 'Sebagian' : 'Belum Dibayar'),
        ]);
    }

    private function recordAudit(Request $request, OnlinePaymentTransaction $transaction): void
    {
        app(AuditLogService::class)->recordOperation(
            $transaction->status === OnlinePaymentTransaction::STATUS_ACCEPTED
                ? 'payments.online.accept'
                : 'payments.online.fail',
            [
                'merchant_order_id' => $transaction->merchant_order_id,
                'provider_reference' => $transaction->provider_reference,
                'result_code' => $transaction->result_code,
                'amount' => $transaction->amount,
                'allocated_amount' => $transaction->allocated_amount,
            ],
            afterValues: [
                'id' => $transaction->id,
                'status' => $transaction->status,
                'reconciliation_status' => $transaction->reconciliation_status,
            ],
            request: $request,
            subjectType: OnlinePaymentTransaction::class,
            subjectId: $transaction->id,
            studentIds: [$transaction->student_id],
        );
    }
}
