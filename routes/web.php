<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuardianPortalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'role.access'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/export', [ReportController::class, 'legacyExport'])->name('reports.export');
    Route::prefix('laporan')->name('reports.')->controller(ReportController::class)->group(function () {
        Route::get('/transaksi', 'transactions')->name('transactions');
        Route::get('/spp-perbulan', 'monthlySpp')->name('monthly_spp');
        Route::get('/spp-belum-bayar', 'outstandingSpp')->name('outstanding_spp');
        Route::get('/spp-tahun-pelajaran', 'yearlySpp')->name('yearly_spp');
        Route::get('/rekap-unit', 'unitRecap')->name('unit_recap');
        Route::get('/{report}/export/xlsx', 'exportXlsx')->whereIn('report', ['transaksi', 'spp-perbulan', 'spp-belum-bayar', 'spp-tahun-pelajaran', 'rekap-unit'])->name('export.xlsx');
        Route::get('/{report}/export/pdf', 'exportPdf')->whereIn('report', ['transaksi', 'spp-perbulan', 'spp-belum-bayar', 'spp-tahun-pelajaran', 'rekap-unit'])->name('export.pdf');
    });
    Route::get('/pengaturan', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/pengaturan', [SettingController::class, 'update'])->name('settings.update');
    Route::get('/wali-santri/tagihan', [GuardianPortalController::class, 'index'])->name('guardian.bills.index');
    Route::post('/wali-santri/transfer', [GuardianPortalController::class, 'store'])->name('guardian.transfers.store');

    require __DIR__.'/master.php';
    require __DIR__.'/student_management.php';
    require __DIR__.'/finance.php';
});
