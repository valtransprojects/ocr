<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\OcrController;

Route::get('/', [OcrController::class, 'index'])->name('ocr.index');
Route::get('/ocr', [OcrController::class, 'index']);
Route::post('/ocr/process', [OcrController::class, 'process'])->name('ocr.process');

// Batch / Back-to-Back Upload & Database Persistence Routes
Route::get('/ocr/batch', [OcrController::class, 'batchIndex'])->name('ocr.batch');
Route::post('/ocr/batch/process', [OcrController::class, 'batchProcess'])->name('ocr.batch.process');
Route::get('/ocr/batch/records', [OcrController::class, 'batchRecords'])->name('ocr.batch.records');
Route::post('/ocr/batch/record/{id}/accept-logic', [OcrController::class, 'acceptLogic'])->name('ocr.batch.acceptLogic');
Route::match(['delete', 'post'], '/ocr/batch/record/{id}/delete', [OcrController::class, 'deleteRecord'])->name('ocr.batch.deleteRecord');
Route::get('/plateImages/{filename}', [OcrController::class, 'serveImage'])
    ->where('filename', '[^/]+')
    ->name('ocr.image');
