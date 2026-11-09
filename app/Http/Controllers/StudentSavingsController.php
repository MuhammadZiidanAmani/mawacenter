<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class StudentSavingsController extends Controller
{
    public function index(): View
    {
        $accounts = $this->mockAccounts();
        $gatewayDeposits = $this->mockGatewayDeposits();

        return view('finance.student-savings', [
            'accounts' => $accounts,
            'totalBalance' => (int) $accounts->sum('balance'),
            'gatewayDeposits' => $gatewayDeposits,
            'failedGatewayDeposits' => $gatewayDeposits->where('status_key', 'failed')->count(),
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
        ]);
    }

    public function show(string $nis): View
    {
        $account = $this->mockAccounts()->firstWhere('nis', $nis);
        abort_unless($account, 404);

        $openingBalance = (int) $account['balance'] - 250000;
        $mutations = $this->mockMutations((int) $account['balance']);

        return view('finance.student-savings-show', [
            'account' => $account,
            'mutations' => $mutations->reverse()->values(),
            'openingBalance' => $openingBalance,
            'totalDeposits' => (int) $mutations->where('amount', '>', 0)->sum('amount'),
            'totalUsage' => abs((int) $mutations->where('type_key', 'usage')->sum('amount')),
            'totalCashWithdrawals' => abs((int) $mutations->where('type_key', 'withdrawal')->sum('amount')),
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('finance.student-savings-create', [
            'accounts' => $this->mockAccountsFor($request),
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'transactionAt' => now(),
        ]);
    }

    public function withdraw(Request $request): View
    {
        return view('finance.student-savings-withdraw', [
            'accounts' => $this->mockAccountsFor($request),
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'transactionAt' => now(),
        ]);
    }

    public function recap(Request $request): View
    {
        $accounts = $this->mockAccountsFor($request);
        $grandTotal = (int) $accounts->sum('balance');
        $unitNames = [
            'RA' => 'Raudhatul Athfal',
            'MI' => 'Madrasah Ibtidaiyah',
            'MTs' => 'Madrasah Tsanawiyah',
            'MA' => 'Madrasah Aliyah',
            'PONPES' => 'Pondok Pesantren',
        ];
        $rows = $accounts
            ->groupBy('unit')
            ->map(function (Collection $unitAccounts, string $unit) use ($grandTotal, $unitNames): array {
                $total = (int) $unitAccounts->sum('balance');

                return [
                    'unit' => $unit,
                    'name' => $unitNames[$unit] ?? $unit,
                    'accounts' => $unitAccounts->count(),
                    'total' => $total,
                    'average' => (int) round($unitAccounts->avg('balance')),
                    'percentage' => $grandTotal > 0 ? round(($total / $grandTotal) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('total')
            ->values();

        return view('finance.student-savings-recap', [
            'rows' => $rows,
            'unitCount' => $rows->count(),
            'accountCount' => $accounts->count(),
            'grandTotal' => $grandTotal,
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
        ]);
    }

    public function guardianIndex(Request $request): View
    {
        abort_unless($request->user()?->isGuardian(), 403);

        $accounts = $this->mockGuardianAccounts($request);

        return view('guardian.student-savings', [
            'accounts' => $accounts,
            'totalBalance' => (int) $accounts->sum('balance'),
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
        ]);
    }

    public function guardianShow(Request $request, Student $student): View
    {
        abort_unless($request->user()?->isGuardian(), 403);

        $account = $this->mockGuardianAccounts($request)->firstWhere('id', $student->id);
        abort_unless($account, 404);

        $openingBalance = (int) $account['balance'] - 250000;
        $mutations = $this->mockMutations((int) $account['balance']);

        return view('guardian.student-savings-show', [
            'account' => $account,
            'mutations' => $mutations->reverse()->values(),
            'openingBalance' => $openingBalance,
            'totalDeposits' => (int) $mutations->where('amount', '>', 0)->sum('amount'),
            'totalUsage' => abs((int) $mutations->where('type_key', 'usage')->sum('amount')),
            'totalCashWithdrawals' => abs((int) $mutations->where('type_key', 'withdrawal')->sum('amount')),
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
        ]);
    }

    /** @return Collection<int, array<string, int|string>> */
    private function mockAccounts(): Collection
    {
        return collect([
            ['nis' => 'SIM-260184', 'name' => 'Ahmad Fauzan', 'unit' => 'MI', 'class' => 'VI A', 'balance' => 850000, 'last_activity' => '08/11/2026'],
            ['nis' => 'SIM-260245', 'name' => 'Aisyah Rahma', 'unit' => 'RA', 'class' => 'B1', 'balance' => 425000, 'last_activity' => '07/11/2026'],
            ['nis' => 'SIM-260072', 'name' => 'Rafi Akbar', 'unit' => 'MTs', 'class' => 'VIII B', 'balance' => 1250000, 'last_activity' => '06/11/2026'],
            ['nis' => 'SIM-260155', 'name' => 'Faris Maulana', 'unit' => 'MA', 'class' => 'XI IPA', 'balance' => 675000, 'last_activity' => '05/11/2026'],
            ['nis' => 'SIM-PP031', 'name' => 'Salwa Azzahra', 'unit' => 'PONPES', 'class' => 'Hifdz 2', 'balance' => 950000, 'last_activity' => '04/11/2026'],
        ]);
    }

    /** @return Collection<int, array<string, int|string>> */
    private function mockAccountsFor(Request $request): Collection
    {
        $accounts = $this->mockAccounts();

        if ($request->user()?->isSuperAdmin() ?? false) {
            return $accounts;
        }

        $unitCodes = $request->user()?->educationUnits()->pluck('code') ?? collect();

        return $accounts->whereIn('unit', $unitCodes)->values();
    }

    /** @return Collection<int, array<string, int|string>> */
    private function mockGuardianAccounts(Request $request): Collection
    {
        $studentIds = $request->user()?->accessibleStudentIds() ?? [];
        $balances = [850000, 425000, 675000, 950000];
        $lastActivities = ['08/11/2026', '07/11/2026', '05/11/2026', '04/11/2026'];

        return Student::query()
            ->with(['schoolClass.educationUnit'])
            ->whereIn('id', $studentIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->values()
            ->map(fn (Student $student, int $index): array => [
                'id' => $student->id,
                'nis' => $student->nis,
                'name' => $student->name,
                'unit' => $student->schoolClass?->educationUnit?->code ?? '-',
                'class' => $student->schoolClass?->name ?? '-',
                'balance' => $balances[$index % count($balances)],
                'last_activity' => $lastActivities[$index % count($lastActivities)],
            ]);
    }

    /** @return Collection<int, array<string, int|string>> */
    private function mockMutations(int $currentBalance): Collection
    {
        $runningBalance = $currentBalance - 250000;

        return collect([
            ['date' => '05/11/2026 08.15 WIB', 'type' => 'Setoran', 'type_key' => 'deposit', 'description' => 'Setoran loket sekolah', 'actor' => 'Kasir · Nabila A.', 'amount' => 300000],
            ['date' => '06/11/2026 11.20 WIB', 'type' => 'Pemakaian Sekolah', 'type_key' => 'usage', 'description' => 'Pembelian perlengkapan belajar', 'actor' => 'Kasir · Nabila A.', 'amount' => -100000],
            ['date' => '07/11/2026 09.05 WIB', 'type' => 'Setoran', 'type_key' => 'deposit', 'description' => 'Setoran wali santri', 'actor' => 'Kasir · Rina S.', 'amount' => 150000],
            ['date' => '08/11/2026 13.40 WIB', 'type' => 'Penarikan Tunai', 'type_key' => 'withdrawal', 'description' => 'Diserahkan kepada wali santri', 'actor' => 'Kasir · Rina S.', 'amount' => -100000],
        ])->map(function (array $mutation) use (&$runningBalance): array {
            $runningBalance += $mutation['amount'];

            return [...$mutation, 'balance' => $runningBalance];
        });
    }

    /** @return Collection<int, array<string, int|string|null>> */
    private function mockGatewayDeposits(): Collection
    {
        return collect([
            ['reference' => 'GW-20261109-0842', 'date' => '09/11/2026 08.42 WIB', 'name' => 'Ahmad Fauzan', 'nis' => 'SIM-260184', 'channel' => 'QRIS', 'amount' => 100000, 'status' => 'Berhasil', 'status_key' => 'success', 'failure_reason' => null],
            ['reference' => 'GW-20261109-0815', 'date' => '09/11/2026 08.15 WIB', 'name' => 'Aisyah Rahma', 'nis' => 'SIM-260245', 'channel' => 'BNI Virtual Account', 'amount' => 75000, 'status' => 'Diproses', 'status_key' => 'pending', 'failure_reason' => null],
            ['reference' => 'GW-20261108-1950', 'date' => '08/11/2026 19.50 WIB', 'name' => 'Rafi Akbar', 'nis' => 'SIM-260072', 'channel' => 'QRIS', 'amount' => 200000, 'status' => 'Gagal', 'status_key' => 'failed', 'failure_reason' => 'Pembayaran melewati batas waktu'],
            ['reference' => 'GW-20261108-1712', 'date' => '08/11/2026 17.12 WIB', 'name' => 'Salwa Azzahra', 'nis' => 'SIM-PP031', 'channel' => 'BNI Virtual Account', 'amount' => 150000, 'status' => 'Gagal', 'status_key' => 'failed', 'failure_reason' => 'Nomor virtual account tidak dikenali'],
        ]);
    }
}
