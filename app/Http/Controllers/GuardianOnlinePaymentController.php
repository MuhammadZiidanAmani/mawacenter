<?php

namespace App\Http\Controllers;

use App\Exceptions\DuitkuException;
use App\Models\Bill;
use App\Models\OnlinePaymentTransaction;
use App\Models\Student;
use App\Services\AuditLogService;
use App\Services\DuitkuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GuardianOnlinePaymentController extends Controller
{
    public function store(Request $request, DuitkuService $duitku): JsonResponse
    {
        $studentIds = $request->user()->accessibleStudentIds() ?? [];
        $validated = $request->validate([
            'student_id' => ['required', 'integer', Rule::in($studentIds)],
            'bill_ids' => ['required', 'array', 'min:1', 'max:50'],
            'bill_ids.*' => ['required', 'integer', 'distinct'],
            'payment_method' => ['required', 'string', 'size:2', 'regex:/^[A-Z0-9]{2}$/'],
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $bills = $this->eligibleBills($validated['bill_ids'], (int) $student->id)->get();
        $this->ensureAllBillsAreEligible($bills, $validated['bill_ids']);
        $amount = (int) $bills->sum('remaining_amount');

        if ($amount < 10000) {
            throw ValidationException::withMessages([
                'bill_ids' => 'Total tagihan untuk pembayaran online minimal Rp10.000.',
            ]);
        }

        try {
            $method = $duitku->paymentMethod($amount, $validated['payment_method']);
        } catch (DuitkuException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], config('services.duitku.enabled') ? 502 : 503);
        }

        $transaction = DB::transaction(function () use ($request, $student, $validated, $amount, $method) {
            $lockedBills = $this->eligibleBills($validated['bill_ids'], (int) $student->id)
                ->lockForUpdate()
                ->get();
            $this->ensureAllBillsAreEligible($lockedBills, $validated['bill_ids']);

            if ((int) $lockedBills->sum('remaining_amount') !== $amount) {
                throw ValidationException::withMessages([
                    'bill_ids' => 'Nominal tagihan berubah. Muat ulang halaman sebelum melanjutkan.',
                ]);
            }

            return OnlinePaymentTransaction::create([
                'user_id' => $request->user()->id,
                'student_id' => $student->id,
                'merchant_order_id' => 'MWA-'.now()->format('YmdHis').'-'.Str::upper(Str::random(12)),
                'bill_ids' => $lockedBills->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'payment_method' => $method['code'],
                'payment_method_name' => $method['name'],
                'amount' => $amount,
                'service_fee' => $method['fee'],
                'total_amount' => $amount + $method['fee'],
                'status' => OnlinePaymentTransaction::STATUS_PROCESSING,
            ]);
        });

        $items = $bills->map(fn (Bill $bill) => [
            'name' => Str::limit($bill->title, 255, ''),
            'price' => (int) $bill->remaining_amount,
            'quantity' => 1,
        ])->values()->all();

        try {
            $provider = $duitku->createTransaction($transaction, $request->user(), $student, $items);
        } catch (DuitkuException $exception) {
            $transaction->update([
                'status' => OnlinePaymentTransaction::STATUS_FAILED,
                'failure_reason' => Str::limit($exception->getMessage(), 255, ''),
            ]);

            return response()->json([
                'message' => 'Transaksi belum dapat dibuat. Silakan coba kembali.',
            ], 502);
        }

        $transaction->update([
            'status' => OnlinePaymentTransaction::STATUS_PENDING,
            'provider_reference' => $provider['reference'],
            'payment_url' => $provider['payment_url'],
            'va_number' => $provider['va_number'],
            'qr_string' => $provider['qr_string'],
            'app_url' => $provider['app_url'],
            'expires_at' => $provider['expires_at'],
            'provider_response' => $provider['response'],
        ]);

        app(AuditLogService::class)->recordOperation(
            'payments.online.create',
            [
                'merchant_order_id' => $transaction->merchant_order_id,
                'bill_ids' => $transaction->bill_ids,
                'payment_method' => $transaction->payment_method,
                'amount' => $transaction->amount,
                'service_fee' => $transaction->service_fee,
            ],
            afterValues: [
                'id' => $transaction->id,
                'status' => $transaction->status,
                'provider_reference' => $transaction->provider_reference,
            ],
            request: $request,
            subjectType: OnlinePaymentTransaction::class,
            subjectId: $transaction->id,
            studentIds: [$student->id],
        );

        return response()->json([
            'message' => 'Transaksi pembayaran berhasil dibuat.',
            'transaction' => [
                'id' => $transaction->id,
                'merchant_order_id' => $transaction->merchant_order_id,
                'reference' => $transaction->provider_reference,
                'payment_method' => $transaction->payment_method,
                'payment_method_name' => $transaction->payment_method_name,
                'amount' => $transaction->amount,
                'service_fee' => $transaction->service_fee,
                'total_amount' => $transaction->total_amount,
                'status' => $transaction->status,
                'payment_url' => $transaction->payment_url,
                'va_number' => $transaction->va_number,
                'qr_string' => $transaction->qr_string,
                'app_url' => $transaction->app_url,
                'expires_at' => $transaction->expires_at?->toIso8601String(),
            ],
        ], 201);
    }

    private function eligibleBills(array $billIds, int $studentId)
    {
        return Bill::query()
            ->whereIn('id', $billIds)
            ->where('student_id', $studentId)
            ->where('status', '!=', 'Dibatalkan')
            ->where('remaining_amount', '>', 0)
            ->where(function ($query) {
                $query->where('source_type', '!=', 'fee_type')
                    ->orWhereNull('fee_type_id')
                    ->orWhereHas('feeType', function ($query) {
                        $query->whereNull('payment_group')
                            ->orWhere('payment_group', '!=', 'laundry');
                    });
            });
    }

    private function ensureAllBillsAreEligible($bills, array $billIds): void
    {
        if ($bills->count() !== count(array_unique($billIds))) {
            throw ValidationException::withMessages([
                'bill_ids' => 'Pilihan tagihan belum valid, sudah lunas, atau tidak dapat dibayar secara online.',
            ]);
        }
    }
}
