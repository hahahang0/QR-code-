<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\QrManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', AdminDashboardController::class)->name('admin.dashboard');
Route::get('/admin/people/export', [PersonController::class, 'export'])->name('admin.people.export');
Route::get('/admin/people/create', [PersonController::class, 'create'])->name('admin.people.create');
Route::resource('/admin/people', PersonController::class)->names('admin.people')->parameters(['people' => 'person'])->except(['create', 'store', 'edit', 'update', 'destroy']);
Route::post('/admin/people', [PersonController::class, 'store'])->middleware('throttle:20,1')->name('admin.people.store');
Route::get('/admin/people/{person}/edit', [PersonController::class, 'edit'])->name('admin.people.edit');
Route::put('/admin/people/{person}', [PersonController::class, 'update'])->middleware('throttle:30,1')->name('admin.people.update');
Route::delete('/admin/people/{person}', [PersonController::class, 'destroy'])->middleware('throttle:10,1')->name('admin.people.destroy');
Route::post('/admin/people/{person}/qr-codes', [QrManagementController::class, 'store'])->middleware('throttle:30,1')->name('admin.people.qr-codes.store');
Route::get('/admin/qr-codes', [QrManagementController::class, 'index'])->name('admin.qr-codes.index');
Route::get('/admin/qr-codes/{qrCode}/{format}/download', [QrManagementController::class, 'download'])->name('admin.qr-codes.download');
Route::get('/admin/qr-codes/{qrCode}/{format}/preview', [QrManagementController::class, 'preview'])->name('admin.qr-codes.preview');
Route::patch('/admin/qr-codes/{qrCode}/deactivate', [QrManagementController::class, 'deactivate'])->middleware('throttle:30,1')->name('admin.qr-codes.deactivate');
Route::get('/admin/activity-logs', [ActivityLogController::class, 'index'])->name('admin.activity-logs.index');
Route::get('/admin/activity-logs/export', [ActivityLogController::class, 'export'])->name('admin.activity-logs.export');
Route::get('/profile/{slug}/photo', [PersonController::class, 'photo'])->name('profiles.photo');
Route::get('/profile/{slug}', [PublicProfileController::class, 'show'])->name('profiles.show');
Route::get('/scanner', [QrCodeController::class, 'scanner'])->name('scanner');
Route::redirect('/admin', '/');
