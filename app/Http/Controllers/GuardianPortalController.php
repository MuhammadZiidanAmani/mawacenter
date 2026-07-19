<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\GuardianTransferRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GuardianPortalController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('finance.bills.index', $request->only('student_id'));
    }

    public function store(Request $request): RedirectResponse
    {
        $studentIds = $request->user()->accessibleStudentIds() ?? [];

        $validated = $request->validate([
            'student_id' => ['required', 'integer', Rule::in($studentIds)],
            'bill_ids' => ['required', 'array', 'min:1'],
            'bill_ids.*' => ['integer', 'exists:bills,id'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        $bills = Bill::whereIn('id', $validated['bill_ids'])
            ->where('student_id', $validated['student_id'])
            ->where('status', '!=', 'Dibatalkan')
            ->where('remaining_amount', '>', 0)
            ->get();

        if ($bills->count() !== count(array_unique($validated['bill_ids']))) {
            throw ValidationException::withMessages([
                'bill_ids' => 'Pilihan tagihan belum valid atau sudah lunas.',
            ]);
        }

        $path = $request->file('proof')->store('guardian-transfer-proofs', 'public');

        GuardianTransferRequest::create([
            'user_id' => $request->user()->id,
            'student_id' => $validated['student_id'],
            'bill_ids' => $bills->pluck('id')->values()->all(),
            'amount' => (int) $bills->sum('remaining_amount'),
            'proof_path' => $path,
            'status' => 'Pending',
        ]);

        return redirect()
            ->route('finance.bills.index', ['student_id' => $validated['student_id']])
            ->with('success', 'Bukti transfer berhasil dikirim dan menunggu verifikasi Super Admin.');
    }
}
