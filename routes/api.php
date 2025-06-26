<?php

use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\SessionController;
use App\Http\Controllers\Api\Admin\EventController;
use App\Http\Controllers\Api\Admin\EventDashboardController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\CollectorReportController;
use App\Http\Controllers\Api\CollectorSessionController;
use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Api\Collector\CollectorReportController as CollectorDonationReportController;
use App\Http\Controllers\Api\Donor\DashboardController as DonorDashboardController;
use App\Http\Controllers\Api\DonationTypeController;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\DonorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('its.auth')->get('/me', function (Request $request) {
    $user = $request->attributes->get('admin');

    return response()->json([
        'id' => $user->id,
        'its_id' => $user->its_id,
        'fullname' => $user->fullname,
        'role' => $user->role,
        'permissions' => [
            'can_create_events' => in_array($user->role, ['admin']),
            'can_manage_events' => in_array($user->role, ['admin']),
            'can_record_donations' => in_array($user->role, ['admin', 'collector']),
            'can_view_sessions' => in_array($user->role, ['admin', 'collector']),
            'can_access_admin' => in_array($user->role, ['admin']),
            'can_manage_users' => in_array($user->role, ['admin']),
            'can_view_collector_report' => in_array($user->role, ['admin']),
            'can_view_collector_dashboard_link' => in_array($user->role, ['admin']),
        ]
    ]);
});

Route::middleware('its.auth')->group(function () {
    // Collector Session Routes (for users with 'collector' role)
    Route::middleware('role:collector')->group(function () {
        Route::post('/collector-sessions/start', [CollectorSessionController::class, 'startSession']);
        Route::post('/collector-sessions/end', [CollectorSessionController::class, 'endSession']);
        Route::get('/collector-sessions/status', [CollectorSessionController::class, 'status']);
        Route::get('/collector/donations', [\App\Http\Controllers\Api\Collector\DashboardController::class, 'getDonations']);
        Route::get('/collector/donations/export', [CollectorDonationReportController::class, 'exportDonations']);
        
        // Donation Routes (accessible by admin and collector via hierarchical permissions)
        Route::get('/donors/search', [DonorController::class, 'search']);
        Route::post('/donations', [DonationController::class, 'store']);
        Route::get('/donation-types', [DonationTypeController::class, 'index']);
        Route::get('/currencies', [CurrencyController::class, 'index']);
        Route::get('/donors/{itsId}', [DonorController::class, 'getDonorByItsId']);
    });
});

// Admin Dashboard Routes
Route::middleware(['its.auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    
    // Event-based dashboard routes
    Route::get('/events/{eventId}/dashboard', [EventDashboardController::class, 'show']);
    Route::get('/sessions/{sessionId}/breakdown', [EventDashboardController::class, 'sessionBreakdown']);
    Route::post('/sessions/{sessionId}/reconcile', [EventDashboardController::class, 'reconcileSession']);
    
    // User management routes
    Route::get('/users', [UserManagementController::class, 'index']);
    Route::post('/users', [UserManagementController::class, 'store']);
    Route::put('/users/{id}/activate', [UserManagementController::class, 'activate']);
    Route::put('/users/{id}/deactivate', [UserManagementController::class, 'deactivate']);
    Route::delete('/users/{id}', [UserManagementController::class, 'archive']);
    Route::get('/mumineen/{itsId}', [UserManagementController::class, 'getMumineenByItsId']);

    // Reports
    Route::get('/reports/summary', [\App\Http\Controllers\Api\Admin\ReportController::class, 'getSummaryReport']);
    Route::get('/reports/detailed', [\App\Http\Controllers\Api\Admin\ReportController::class, 'getDetailedReport']);
    Route::get('/reports/summary/export', [\App\Http\Controllers\Api\Admin\ReportController::class, 'exportSummary']);
    Route::get('/reports/detailed/export', [\App\Http\Controllers\Api\Admin\ReportController::class, 'exportDetailed']);
    Route::get('/reports/zabihat-count', [\App\Http\Controllers\Api\Admin\ReportController::class, 'getZabihatCount']);
    
    Route::post('/admins', [AdminController::class, 'store']);
    Route::put('/admins/{admin}', [AdminController::class, 'update']);
    Route::delete('/admins/{admin}', [AdminController::class, 'destroy']);
    Route::get('/roles', [AdminController::class, 'getRoles']);
    Route::get('/sessions', [SessionController::class, 'index']);
    Route::get('/donations/recent', [AdminDashboardController::class, 'recentDonations']);
    Route::post('/encrypt-its-id', [AdminController::class, 'encryptItsId']);
    Route::post('/events', [EventController::class, 'store']);
    Route::get('/events', [EventController::class, 'index']);
    Route::put('/events/{event}', [EventController::class, 'update']);
    Route::put('/events/{event}/set-active', [EventController::class, 'setActive']);
    Route::put('/events/{event}/deactivate', [EventController::class, 'deactivate']);

    // Collector Report Routes
    Route::get('/reports/collector/detailed', [CollectorReportController::class, 'getDetailedReport']);
    Route::get('/reports/collector/summary', [CollectorReportController::class, 'getSummaryReport']);
});

// Donor Dashboard Routes
Route::middleware('its.auth')->prefix('donor')->group(function () {
    Route::get('/dashboard', [DonorDashboardController::class, 'index']);
    // Future donor routes can be added here
});
