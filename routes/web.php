<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\QueriesController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/cache/clear', [DashboardController::class, 'clearCache'])->name('cache.clear');
Route::get('/queries', [QueriesController::class, 'index'])->name('queries');
Route::post('/queries/refresh', [QueriesController::class, 'refresh'])->name('queries.refresh');
Route::get('/queries/pdf', [QueriesController::class, 'exportPdf'])->name('queries.pdf');
Route::get('/distribution/pdf', [DashboardController::class, 'exportDistributionPdf'])->name('distribution.pdf');
Route::get('/distribution/resume/pdf', [DashboardController::class, 'exportDistributionResumePdf'])->name('distribution.resume.pdf');
Route::get('/rapport/pdf', [DashboardController::class, 'exportRapportPdf'])->name('rapport.pdf');
Route::post('/performance/pdf', [DashboardController::class, 'exportPerformancePdf'])->name('performance.pdf');
