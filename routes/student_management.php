<?php

use App\Http\Controllers\MasterDataController;
use Illuminate\Support\Facades\Route;

Route::prefix('manajemen-siswa')->name('student-management.')->controller(MasterDataController::class)->group(function () {
    Route::redirect('/', '/manajemen-siswa/data-siswa')->name('index');
    Route::get('/data-siswa', 'studentIndex')->name('students.index');
    Route::get('/data-siswa/create', 'studentCreate')->name('students.create');
    Route::get('/data-siswa/import', 'studentImport')->name('students.import');
    Route::get('/data-siswa/jadikan-alumni-kelas', 'classAlumniCreate')->name('students.class-alumni.create');
    Route::post('/data-siswa/jadikan-alumni-kelas', 'storeClassAlumni')->name('students.class-alumni.store');
    Route::get('/pindah-kelas', 'studentTransfer')->name('class-transfer.index');
    Route::post('/pindah-kelas', 'storeStudentTransfer')->name('class-transfer.store');
    Route::get('/naik-kelas', 'studentPromotion')->name('class-promotion.index');
    Route::post('/naik-kelas', 'storeStudentPromotion')->name('class-promotion.store');
    Route::get('/alumni', 'studentAlumni')->name('alumni.index');
});
