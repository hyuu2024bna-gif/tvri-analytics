<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacebookAuthController;
use App\Http\Controllers\InstagramAuthController;
use App\Http\Controllers\ManualInputController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\YoutubeTestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/youtube-test', [YoutubeTestController::class, 'form'])->name('youtube.test');
    Route::post('/youtube-test', [YoutubeTestController::class, 'fetch'])->name('youtube.fetch');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/reports', [ReportController::class, 'form'])->name('reports.form');
    Route::get('/reports/pdf', [ReportController::class, 'exportPdf'])->name('reports.pdf');
    Route::get('/reports/excel', [ReportController::class, 'exportExcel'])->name('reports.excel');
    Route::get('/reports/pdf-visual', [ReportController::class, 'exportVisualPdf'])->name('reports.pdf-visual');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/manual', [ManualInputController::class, 'index'])->name('manual.index');
    Route::get('/manual/create', [ManualInputController::class, 'createContent'])->name('manual.create');
    Route::post('/manual', [ManualInputController::class, 'storeContent'])->name('manual.store');
    Route::get('/manual/{content}/input', [ManualInputController::class, 'createInput'])->name('manual.input.create');
    Route::post('/manual/{content}/input', [ManualInputController::class, 'storeInput'])->name('manual.input.store');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/instagram/connect', [InstagramAuthController::class, 'connect'])->name('instagram.connect');
    Route::get('/instagram/callback', [InstagramAuthController::class, 'callback'])->name('instagram.callback');
    Route::get('/instagram/status', [InstagramAuthController::class, 'status'])->name('instagram.status');
    Route::get('/instagram/test', [InstagramAuthController::class, 'test'])->name('instagram.test');
    Route::get('/instagram/test-media', [InstagramAuthController::class, 'testMedia'])->name('instagram.test-media');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/facebook/test', [FacebookAuthController::class, 'test'])->name('facebook.test');
    Route::get('/facebook/test-posts', [FacebookAuthController::class, 'testPosts'])->name('facebook.test-posts');
});

require __DIR__.'/auth.php';

