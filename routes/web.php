<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DigitRecognitionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [DigitRecognitionController::class, 'index'])->name('home');

// مسارات API للتعرف على الأرقام
Route::post('/api/recognize', [DigitRecognitionController::class, 'recognize'])->name('recognize');
Route::post('/api/retrain', [DigitRecognitionController::class, 'retrain'])->name('retrain');
Route::get('/api/model-info', [DigitRecognitionController::class, 'modelInfo'])->name('model-info');
