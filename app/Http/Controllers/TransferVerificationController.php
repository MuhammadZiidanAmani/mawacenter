<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\GuardianTransferRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransferVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->string('status')->value(), ['Pending', 'Diterima', 'Ditolak'], true)
            ? $request->string('status')->value()
            : 'Pending';

        $requests = GuardianTransferRequest::with(['student.schoolClass.educationUnit', 'user:id,name,username', 'verifier:id,name'])
            ->where('status', $status)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('finance.transfer-verifications', [
            'requests' => $requests,
            'status' => $status,
        ]);
    }

    public function accept(Request $request, GuardianTransferRequest $transfer): RedirectResponse
    {
        if ($transfer->status !== 'Pending') {
            return redirect()->route('finance.transfer-verifications.index')->with('error', 'Transfer ini sudah diproses.');
        }

        DB::transaction(function () use ($request, $transfer) {
            $remainingTransfer = (int) $transfer->amount;
            $bills = Bill::whereIn('id', $transfer->bill_ids ?? [])
                ->where('student_id', $transfer->student_id)
                ->where('status', '!=', 'Dibatalkan')
                ->orderByRaw("CASE WHEN source_type = 'spp' THEN 0 ELSE 1 END")
                ->orderBy('year')
                ->orderBy('month')
                ->lockForUpdate()
                ->get();

            foreach ($bills as $bill) {
                if ($remainingTransfer < 1 || $bill->remaining_amount < 1) {
                    continue;
                }

                $allocated = min($remainingTransfer, (int) $bill->remaining_amount);
                $bill->allocations()->updateOrCreate(
                    ['payment_type' => 'guardian_transfer', 'payment_id' => $transfer->id],
                    ['amount' => $allocated],
                );
                $remainingTransfer -= $allocated;
                $this->refreshBill($bill);
            }

            $transfer->update([
                'status' => 'Diterima',
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'rejected_reason' => null,
            ]);
        });

        return redirect()->route('finance.transfer-verifications.index')->with('success', 'Transfer wali santri diterima dan tagihan diperbarui.');
    }

    public function reject(Request $request, GuardianTransferRequest $transfer): RedirectResponse
    {
        $validated = $request->validate([
            'rejected_reason' => ['required', 'string', 'max:500'],
        ]);

        if ($transfer->status !== 'Pending') {
            return redirect()->route('finance.transfer-verifications.index')->with('error', 'Transfer ini sudah diproses.');
        }

        $transfer->update([
            'status' => 'Ditolak',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'rejected_reason' => $validated['rejected_reason'],
        ]);

        return redirect()->route('finance.transfer-verifications.index')->with('success', 'Transfer wali santri ditolak.');
    }

    private function refreshBill(Bill $bill): void
    {
        $paid = min($bill->total_amount, (int) $bill->allocations()->sum('amount'));
        $remaining = max(0, (int) $bill->total_amount - $paid);
        $bill->update([
            'paid_amount' => $paid,
            'remaining_amount' => $remaining,
            'status' => $remaining === 0 ? 'Lunas' : ($paid > 0 ? 'Sebagian' : 'Belum Dibayar'),
        ]);
    }
}
