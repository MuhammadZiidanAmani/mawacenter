<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\GuardianTransferRequest;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransferVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $unitIds = $request->user()?->accessibleUnitIds();
        $status = in_array($request->string('status')->value(), ['Pending', 'Diterima', 'Ditolak'], true)
            ? $request->string('status')->value()
            : 'Pending';

        $requests = GuardianTransferRequest::with(['student.schoolClass.educationUnit', 'user:id,name,username', 'verifier:id,name'])
            ->where('status', $status)
            ->when(is_array($unitIds), function ($query) use ($unitIds) {
                $unitIds === []
                    ? $query->whereRaw('1 = 0')
                    : $query->whereHas('student.schoolClass', fn ($class) => $class->whereIn('education_unit_id', $unitIds));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('finance.transfer-verifications', [
            'requests' => $requests,
            'status' => $status,
        ]);
    }

    public function proof(Request $request, GuardianTransferRequest $transfer): StreamedResponse
    {
        $this->authorizeTransferAccess($request, $transfer);

        if (! $transfer->proof_path) {
            abort(404);
        }

        if (Storage::disk('local')->exists($transfer->proof_path)) {
            return Storage::disk('local')->response($transfer->proof_path);
        }

        abort(404);
    }

    public function accept(Request $request, GuardianTransferRequest $transfer): RedirectResponse
    {
        $this->authorizeTransferAccess($request, $transfer);
        $before = $this->transferAuditSnapshot($transfer->loadMissing('student.schoolClass'));

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
        $transfer->refresh();
        app(AuditLogService::class)->recordOperation(
            'payments.transfer.accept',
            $this->transferAuditMetadata($transfer),
            beforeValues: $before,
            afterValues: $this->transferAuditSnapshot($transfer),
            request: $request,
            subjectType: GuardianTransferRequest::class,
            subjectId: $transfer->id,
            studentIds: [$transfer->student_id],
        );

        return redirect()->route('finance.transfer-verifications.index')->with('success', 'Transfer wali santri diterima dan tagihan diperbarui.');
    }

    public function reject(Request $request, GuardianTransferRequest $transfer): RedirectResponse
    {
        $this->authorizeTransferAccess($request, $transfer);
        $before = $this->transferAuditSnapshot($transfer->loadMissing('student.schoolClass'));

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
        $transfer->refresh();
        app(AuditLogService::class)->recordOperation(
            'payments.transfer.reject',
            $this->transferAuditMetadata($transfer) + ['reason' => $validated['rejected_reason']],
            beforeValues: $before,
            afterValues: $this->transferAuditSnapshot($transfer),
            request: $request,
            subjectType: GuardianTransferRequest::class,
            subjectId: $transfer->id,
            studentIds: [$transfer->student_id],
        );

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

    private function authorizeTransferAccess(Request $request, GuardianTransferRequest $transfer): void
    {
        $unitIds = $request->user()?->accessibleUnitIds();
        if (! is_array($unitIds)) {
            return;
        }

        $transfer->loadMissing('student.schoolClass');
        abort_unless(in_array((int) $transfer->student?->schoolClass?->education_unit_id, $unitIds, true), 403);
    }

    private function transferAuditMetadata(GuardianTransferRequest $transfer): array
    {
        $transfer->loadMissing('student.schoolClass');

        return [
            'student_id' => $transfer->student_id,
            'unit_id' => $transfer->student?->schoolClass?->education_unit_id,
            'status' => $transfer->status,
            'amount' => (int) $transfer->amount,
        ];
    }

    private function transferAuditSnapshot(GuardianTransferRequest $transfer): array
    {
        return [
            'id' => $transfer->id,
            'student_id' => $transfer->student_id,
            'bill_ids' => $transfer->bill_ids ?? [],
            'amount' => (int) $transfer->amount,
            'status' => $transfer->status,
            'verified_by' => $transfer->verified_by,
            'verified_at' => $transfer->verified_at?->format('Y-m-d H:i:s'),
            'rejected_reason' => $transfer->rejected_reason,
        ];
    }
}
