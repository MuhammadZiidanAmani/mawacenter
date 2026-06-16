<?php

use App\Http\Controllers\MasterDataController;
use Illuminate\Support\Facades\Route;

Route::prefix('master-data')->name('master.')->controller(MasterDataController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/academic-years', 'storeAcademicYear')->name('academic-years.store');
    Route::put('/academic-years/{academicYear}', 'updateAcademicYear')->name('academic-years.update');
    Route::post('/classes', 'storeClass')->name('classes.store');
    Route::put('/classes/{schoolClass}', 'updateClass')->name('classes.update');
    Route::post('/education-units', 'storeEducationUnit')->name('education-units.store');
    Route::put('/education-units/{educationUnit}', 'updateEducationUnit')->name('education-units.update');
    Route::post('/students', 'storeStudent')->name('students.store');
    Route::put('/students/{student}', 'updateStudent')->name('students.update');
    Route::get('/students/export', 'exportStudents')->name('students.export');
    Route::get('/students/template', 'studentTemplate')->name('students.template');
    Route::post('/students/import/preview', 'previewStudentImport')->name('students.import.preview');
    Route::post('/students/import', 'importStudents')->name('students.import');
    Route::post('/fee-types', 'storeFeeType')->name('fee-types.store');
    Route::put('/fee-types/{feeType}', 'updateFeeType')->name('fee-types.update');
    Route::post('/fee-discounts', 'storeFeeDiscount')->name('fee-discounts.store');
    Route::put('/fee-discounts/{feeDiscount}', 'updateFeeDiscount')->name('fee-discounts.update');
    Route::delete('/{type}/{id}', 'destroy')->name('destroy');
});
