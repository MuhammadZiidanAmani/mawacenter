<?php

use App\Http\Controllers\MasterDataController;
use Illuminate\Support\Facades\Route;

Route::prefix('manajemen-siswa')->name('student-management.')->controller(MasterDataController::class)->group(function () {
    Route::redirect('/', '/manajemen-siswa/data-siswa')->name('index');
    Route::get('/data-siswa', 'studentIndex')->name('students.index');
    Route::get('/data-siswa/create', 'studentCreate')->name('students.create');
    Route::get('/pindah-kelas', 'studentTransfer')->name('class-transfer.index');
    Route::get('/naik-kelas', 'studentPromotion')->name('class-promotion.index');
    Route::get('/alumni', 'studentAlumni')->name('alumni.index');
});
