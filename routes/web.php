<?php

use App\Http\Controllers\AcceptabiliteController;
use App\Http\Controllers\AdverseEventsSelectionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GpsExportController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\QueriesController;
use App\Http\Controllers\RapportBrasController;
use Illuminate\Support\Facades\Route;

// Auth routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login',          [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',         [AuthController::class, 'login']);
    Route::get('/auth/microsoft', [AuthController::class, 'redirectToMicrosoft'])->name('auth.microsoft');
    Route::get('/auth/microsoft/callback', [AuthController::class, 'handleMicrosoftCallback'])->name('auth.microsoft.callback');
    Route::get('/forgot-password',[AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password',[AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password',[AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/warm', [DashboardController::class, 'warmCache'])->name('dashboard.warm');
    Route::get('/dashboard/section/{tab}', [DashboardController::class, 'section'])->name('dashboard.section');
    Route::post('/cache/clear', [DashboardController::class, 'clearCache'])->name('cache.clear');
    Route::get('/queries', [QueriesController::class, 'index'])->name('queries');
    Route::post('/queries/refresh', [QueriesController::class, 'refresh'])->name('queries.refresh');
    Route::get('/queries/pdf', [QueriesController::class, 'exportPdf'])->name('queries.pdf');
    Route::get('/distribution/pdf', [DashboardController::class, 'exportDistributionPdf'])->name('distribution.pdf');
    Route::get('/distribution/resume/pdf', [DashboardController::class, 'exportDistributionResumePdf'])->name('distribution.resume.pdf');
    Route::get('/rapport/pdf', [DashboardController::class, 'exportRapportPdf'])->name('rapport.pdf');
    Route::post('/performance/pdf', [DashboardController::class, 'exportPerformancePdf'])->name('performance.pdf');
    Route::get('/gps-export',  [GpsExportController::class, 'index'])->name('gps.export');
    Route::post('/gps-export', [GpsExportController::class, 'export'])->name('gps.export.download');

    // Rapport par bras
    Route::get('/rapport/bras',     [RapportBrasController::class, 'index'])->name('rapport.bras');
    Route::get('/rapport/bras/pdf', [RapportBrasController::class, 'pdf'])->name('rapport.bras.pdf');

    // Inspections KoboToolbox
    Route::get('/inspections',              [InspectionController::class, 'index'])->name('inspections');
    Route::get('/inspections/export',       [InspectionController::class, 'export'])->name('inspections.export');
    Route::post('/inspections/refresh',     [InspectionController::class, 'clearCache'])->name('inspections.refresh');

    // Documentation
    Route::get('/docs', fn() => view('docs'))->name('docs');

    // Acceptabilité (Cohorte A — UA + AE tracking)
    Route::get('/acceptabilite',     [AcceptabiliteController::class, 'index'])->name('acceptabilite.index');
    Route::get('/acceptabilite/pdf', [AcceptabiliteController::class, 'pdf'])->name('acceptabilite.pdf');

    // Sélection AE (tirage aléatoire stratifié)
    Route::get('/adverse-events/selection',              [AdverseEventsSelectionController::class, 'index'])->name('ae.selection.index');
    Route::post('/adverse-events/selection/select',      [AdverseEventsSelectionController::class, 'select'])->name('ae.selection.select');
    Route::post('/adverse-events/selection/add-more',    [AdverseEventsSelectionController::class, 'addMore'])->name('ae.selection.add-more');
    Route::post('/adverse-events/selection/reset',       [AdverseEventsSelectionController::class, 'reset'])->name('ae.selection.reset');
    Route::post('/adverse-events/selection/{id}/toggle', [AdverseEventsSelectionController::class, 'toggleCompleted'])->name('ae.selection.toggle');
    Route::get('/adverse-events/selection/excel',        [AdverseEventsSelectionController::class, 'exportExcel'])->name('ae.selection.excel');
    Route::get('/adverse-events/selection/pdf',          [AdverseEventsSelectionController::class, 'exportPdf'])->name('ae.selection.pdf');
});
