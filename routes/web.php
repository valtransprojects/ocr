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
