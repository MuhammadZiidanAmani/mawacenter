<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class GuardianPaymentCartController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isGuardian(), 403);

        $cartStudents = collect([
            [
                'student_name' => 'Ahmad Fauzan',
                'units' => [
                    [
                        'unit_code' => 'MI',
                        'unit_name' => 'MI Mambaul Hikmah',
                        'class_name' => '6A',
                        'nis' => '260184',
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
                        'items' => [
                            ['title' => 'Syahriah November 2026', 'period' => 'November 2026', 'amount' => 100000],
                            ['title' => 'Uang Makan', 'period' => 'November 2026', 'amount' => 175000],
                        ],
                    ],
                ],
            ],
            [
                'student_name' => 'Aisyah Rahma',
                'units' => [
                    [
                        'unit_code' => 'RA',
                        'unit_name' => 'RA Mambaul Hikmah',
                        'class_name' => 'B1',
                        'nis' => '260245',
                        'items' => [
                            ['title' => 'SPP November 2026', 'period' => 'November 2026', 'amount' => 90000],
                        ],
                    ],
                ],
            ],
        ]);
        if ($request->string('mock_state')->toString() === 'empty') {
            $cartStudents = collect();
        }

        $subtotal = (int) $cartStudents->sum(
            fn (array $student) => collect($student['units'])->sum(
                fn (array $unit) => collect($unit['items'])->sum('amount')
            )
        );

        return view('guardian.payment-cart', [
            'cartStudents' => $cartStudents,
            'subtotal' => $subtotal,
            'serviceFee' => $cartStudents->isEmpty() ? 0 : 4000,
        ]);
    }
}
