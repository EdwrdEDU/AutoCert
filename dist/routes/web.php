<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\CertificateController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('templates.index');
});

// Template Routes
Route::prefix('templates')->name('templates.')->group(function () {
    Route::get('/', [TemplateController::class, 'index'])->name('index');
    Route::get('/create', [TemplateController::class, 'create'])->name('create');
    Route::post('/', [TemplateController::class, 'store'])->name('store');
    Route::get('/{template}', [TemplateController::class, 'show'])->name('show');
    Route::put('/{template}/fields', [TemplateController::class, 'updateFields'])->name('update-fields');
    Route::post('/{template}/reanalyze', [TemplateController::class, 'reanalyze'])->name('reanalyze');
    Route::delete('/{template}', [TemplateController::class, 'destroy'])->name('destroy');
});

// Certificate Routes
Route::prefix('certificates')->name('certificates.')->group(function () {
    Route::get('/', [CertificateController::class, 'index'])->name('index');
    Route::get('/template/{template}/create', [CertificateController::class, 'create'])->name('create');

    Route::post('/template/{template}/generate-single', [CertificateController::class, 'generateSingle'])->name('generate-single');
    Route::post('/template/{template}/generate-batch', [CertificateController::class, 'generateBatch'])->name('generate-batch');
    Route::post('/template/{template}/import-csv', [CertificateController::class, 'importCsv'])->name('import-csv');
    Route::get('/template/{template}/batch-results', [CertificateController::class, 'batchResults'])->name('batch-results');
    Route::get('/{certificate}', [CertificateController::class, 'show'])->name('show');
    Route::get('/{certificate}/download', [CertificateController::class, 'download'])->name('download');
    Route::delete('/{certificate}', [CertificateController::class, 'destroy'])->name('destroy');
    Route::post('/export-zip', [CertificateController::class, 'exportZip'])->name('export-zip');
    Route::post('/merge', [CertificateController::class, 'merge'])->name('merge');
});
