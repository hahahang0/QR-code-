<?php

use App\Http\Controllers\QrCodeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [QrCodeController::class, 'index'])->name('qr.index');
Route::post('/qr/generate', [QrCodeController::class, 'generate'])->middleware('throttle:30,1')->name('qr.generate');
Route::get('/scanner', [QrCodeController::class, 'scanner'])->name('scanner');
Route::redirect('/admin', '/');
Route::redirect('/login', '/');
