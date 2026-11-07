<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class GuardianPaymentCartController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isGuardian(), 403);

        $cartGroups = collect([
            [
                'unit_code' => 'MI',
                'unit_name' => 'MI Mambaul Hikmah',
                'class_name' => '6A',
                'nis' => '260184',
                'student_name' => 'Ahmad Fauzan',
                'items' => [
                    ['title' => 'SPP November 2026', 'period' => 'November 2026', 'amount' => 125000],
                    ['title' => 'Daftar Ulang', 'period' => 'Tahun Pelajaran 2026/2027', 'amount' => 250000],
                ],
            ],
            [
                'unit_code' => 'PONPES',
                'unit_name' => 'Pondok Pesantren Mambaul Hikmah',
                'class_name' => 'Hifdz 2',
                'nis' => 'PP-260184',
                'student_name' => 'Ahmad Fauzan',
                'items' => [
                    ['title' => 'Syahriah November 2026', 'period' => 'November 2026', 'amount' => 100000],
                    ['title' => 'Uang Makan', 'period' => 'November 2026', 'amount' => 175000],
                ],
            ],
        ]);
        $subtotal = (int) $cartGroups->sum(fn (array $group) => collect($group['items'])->sum('amount'));

        return view('guardian.payment-cart', [
            'cartGroups' => $cartGroups,
            'subtotal' => $subtotal,
            'serviceFee' => 4000,
        ]);
    }
}
