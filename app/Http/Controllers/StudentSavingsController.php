<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentSavingsController extends Controller
{
    public function index(): View
    {
        $accounts = $this->mockAccounts();

        return view('finance.student-savings', [
            'accounts' => $accounts,
            'totalBalance' => (int) $accounts->sum('balance'),
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
        ]);
    }

    public function show(string $nis): View
    {
        $account = $this->mockAccounts()->firstWhere('nis', $nis);
        abort_unless($account, 404);

        $openingBalance = (int) $account['balance'] - 250000;
        $runningBalance = $openingBalance;
        $mutations = collect([
            ['date' => '05/11/2026 08.15 WIB', 'type' => 'Setoran', 'description' => 'Setoran loket sekolah', 'amount' => 300000],
            ['date' => '06/11/2026 11.20 WIB', 'type' => 'Penarikan', 'description' => 'Keperluan perlengkapan belajar', 'amount' => -100000],
            ['date' => '07/11/2026 09.05 WIB', 'type' => 'Setoran', 'description' => 'Setoran wali santri', 'amount' => 150000],
            ['date' => '08/11/2026 13.40 WIB', 'type' => 'Penarikan', 'description' => 'Keperluan kegiatan santri', 'amount' => -100000],
        ])->map(function (array $mutation) use (&$runningBalance): array {
            $runningBalance += $mutation['amount'];

            return [...$mutation, 'balance' => $runningBalance];
        });

        return view('finance.student-savings-show', [
            'account' => $account,
            'mutations' => $mutations->reverse()->values(),
            'openingBalance' => $openingBalance,
            'totalDeposits' => (int) $mutations->where('amount', '>', 0)->sum('amount'),
            'totalWithdrawals' => abs((int) $mutations->where('amount', '<', 0)->sum('amount')),
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
        ]);
    }

    public function create(Request $request): View
    {
        $accounts = $this->mockAccounts();

        if (! ($request->user()?->isSuperAdmin() ?? false)) {
            $unitCodes = $request->user()?->educationUnits()->pluck('code') ?? collect();
            $accounts = $accounts->whereIn('unit', $unitCodes)->values();
        }

        return view('finance.student-savings-create', [
            'accounts' => $accounts,
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'transactionAt' => now(),
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
}
