<?php

use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\StudentIdentityCleanupController;
use Illuminate\Support\Facades\Route;

Route::prefix('manajemen-siswa')->name('student-management.')->group(function () {
    Route::controller(MasterDataController::class)->group(function () {
        Route::redirect('/', '/manajemen-siswa/data-siswa')->name('index');
        Route::get('/data-siswa', 'studentIndex')->name('students.index');
        Route::get('/data-siswa/create', 'studentCreate')->name('students.create');
        Route::get('/data-siswa/import', 'studentImport')->name('students.import');
        Route::get('/data-siswa/{student}/edit', 'studentEdit')->name('students.edit');
        Route::get('/data-siswa/jadikan-alumni-kelas', 'classAlumniCreate')->name('students.class-alumni.create');
        Route::post('/data-siswa/jadikan-alumni-kelas', 'storeClassAlumni')->name('students.class-alumni.store');
        Route::get('/pindah-kelas', 'studentTransfer')->name('class-transfer.index');
        Route::post('/pindah-kelas', 'storeStudentTransfer')->name('class-transfer.store');
        Route::get('/naik-kelas', 'studentPromotion')->name('class-promotion.index');
        Route::post('/naik-kelas', 'storeStudentPromotion')->name('class-promotion.store');
        Route::get('/alumni', 'studentAlumni')->name('alumni.index');
    });

    Route::get('/rapikan-identitas', [StudentIdentityCleanupController::class, 'index'])->name('identity-cleanup.index');
    Route::get('/rapikan-identitas/tinjau/{candidateKey}', [StudentIdentityCleanupController::class, 'show'])->name('identity-cleanup.show');
    Route::post('/rapikan-identitas/gabungkan', [StudentIdentityCleanupController::class, 'merge'])->name('identity-cleanup.merge');
    Route::post('/rapikan-identitas/pisahkan', [StudentIdentityCleanupController::class, 'split'])->name('identity-cleanup.split');
});
