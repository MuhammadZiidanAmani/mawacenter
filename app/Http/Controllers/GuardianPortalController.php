<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\GuardianTransferRequest;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GuardianPortalController extends Controller
{
    public function index(Request $request): View
    {
        $studentIds = $request->user()->accessibleStudentIds() ?? [];
        $students = Student::with('schoolClass.educationUnit')
            ->whereIn('id', $studentIds)
            ->orderBy('name')
            ->get();

        $selectedStudentId = $request->integer('student_id') ?: $students->first()?->id;
        if ($selectedStudentId && ! in_array($selectedStudentId, $studentIds, true)) {
            abort(403, 'Anda tidak memiliki akses ke tagihan siswa ini.');
        }

        $bills = Bill::with(['feeType:id,name', 'student.schoolClass.educationUnit'])
            ->whereIn('student_id', $studentIds)
            ->when($selectedStudentId, fn ($query) => $query->where('student_id', $selectedStudentId))
            ->where('status', '!=', 'Dibatalkan')
            ->where('remaining_amount', '>', 0)
            ->orderBy('student_id')
            ->orderByRaw("CASE WHEN source_type = 'spp' THEN 0 ELSE 1 END")
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('title')
            ->get();

        $transfers = GuardianTransferRequest::with('student.schoolClass.educationUnit', 'verifier:id,name')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->limit(20)
            ->get();

        return view('guardian.bills', [
            'students' => $students,
            'selectedStudentId' => $selectedStudentId,
            'bills' => $bills,
            'transfers' => $transfers,
        ]);
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
            ->route('guardian.bills.index', ['student_id' => $validated['student_id']])
            ->with('success', 'Bukti transfer berhasil dikirim dan menunggu verifikasi Super Admin.');
    }
}
