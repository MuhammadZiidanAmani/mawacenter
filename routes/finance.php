<?php

use App\Http\Controllers\SppPaymentController;
use App\Http\Controllers\OtherPaymentController;
use App\Http\Controllers\BillController;
use Illuminate\Support\Facades\Route;

Route::prefix('keuangan/pembayaran/spp')->name('finance.spp.')->controller(SppPaymentController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::get('/months', 'months')->name('months');
    Route::get('/quote', 'quote')->name('quote');
    Route::post('/import/preview', 'previewImport')->name('import.preview');
    Route::post('/import', 'import')->name('import');
    Route::post('/', 'store')->name('store');
    Route::post('/{sppPayment}/corrections', 'correct')->name('correct');
    Route::get('/{sppPayment}/receipt', 'receipt')->name('receipt');
    Route::get('/{sppPayment}', 'show')->name('show');
    Route::put('/{sppPayment}', 'update')->name('update');
    Route::delete('/{sppPayment}', 'destroy')->name('destroy');
});

Route::prefix('keuangan/pembayaran/lain-lain')->name('finance.other.')->controller(OtherPaymentController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::get('/quote', 'quote')->name('quote');
    Route::post('/import/preview', 'previewImport')->name('import.preview');
    Route::post('/import', 'import')->name('import');
    Route::post('/', 'store')->name('store');
});

Route::prefix('keuangan/tagihan')->name('finance.bills.')->controller(BillController::class)->group(function () {
    Route::get('/', 'index')->name('index');
});
