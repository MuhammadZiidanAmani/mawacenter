<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');
Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
Route::get('/laporan/export', [ReportController::class, 'export'])->name('reports.export');
Route::get('/pengaturan', [SettingController::class, 'index'])->name('settings.index');
Route::put('/pengaturan', [SettingController::class, 'update'])->name('settings.update');

require __DIR__.'/master.php';
require __DIR__.'/finance.php';
