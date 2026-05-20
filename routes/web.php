<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\QueriesController;
use Illuminate\Support\Facades\Route;

// Auth routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login',          [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',         [AuthController::class, 'login']);
    Route::get('/forgot-password',[AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password',[AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password',[AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/cache/clear', [DashboardController::class, 'clearCache'])->name('cache.clear');
    Route::get('/queries', [QueriesController::class, 'index'])->name('queries');
    Route::post('/queries/refresh', [QueriesController::class, 'refresh'])->name('queries.refresh');
    Route::get('/queries/pdf', [QueriesController::class, 'exportPdf'])->name('queries.pdf');
    Route::get('/distribution/pdf', [DashboardController::class, 'exportDistributionPdf'])->name('distribution.pdf');
    Route::get('/distribution/resume/pdf', [DashboardController::class, 'exportDistributionResumePdf'])->name('distribution.resume.pdf');
    Route::get('/rapport/pdf', [DashboardController::class, 'exportRapportPdf'])->name('rapport.pdf');
    Route::post('/performance/pdf', [DashboardController::class, 'exportPerformancePdf'])->name('performance.pdf');
});
