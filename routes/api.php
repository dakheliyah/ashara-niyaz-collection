<?php

use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\SessionController;
use App\Http\Controllers\Api\Admin\EventController;
use App\Http\Controllers\Api\Admin\EventDashboardController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\CollectorSessionController;
use App\Http\Controllers\Api\DonationController;
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
    $admin = $request->attributes->get('admin');
    
    // Look up the full name from mumineen table
    $mumineen = \App\Models\Mumineen::where('its_id', $admin->its_id)->first();
    $fullName = $mumineen ? $mumineen->fullname : null;
    
    return response()->json([
        'id' => $admin->id,
        'its_id' => $admin->its_id,
        'fullname' => $fullName,
        'role' => $admin->role->name,
        'permissions' => [
            'can_create_events' => in_array($admin->role->name, ['admin']),
            'can_manage_events' => in_array($admin->role->name, ['admin']),
            'can_record_donations' => in_array($admin->role->name, ['admin', 'collector']),
            'can_view_sessions' => in_array($admin->role->name, ['admin', 'collector']),
            'can_access_admin' => in_array($admin->role->name, ['admin']),
            'can_manage_users' => in_array($admin->role->name, ['admin']),
        ]
    ]);
});

Route::middleware('its.auth')->group(function () {
    // Collector Session Routes (for users with 'collector' role)
    Route::middleware('role:collector')->group(function () {
        Route::get('/collector-sessions/status', [CollectorSessionController::class, 'status']);
        Route::post('/collector-sessions/start', [CollectorSessionController::class, 'startSession']);
        Route::post('/collector-sessions/end', [CollectorSessionController::class, 'endSession']);
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
    Route::post('/users/{userId}/deactivate', [UserManagementController::class, 'deactivate']);
    Route::post('/users/{userId}/archive', [UserManagementController::class, 'archive']);
    Route::post('/users/{userId}/activate', [UserManagementController::class, 'activate']);
    Route::get('/collectors/{itsId}/status', [UserManagementController::class, 'checkCollectorStatus']);
    
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
});

// Donor Dashboard Routes
Route::middleware('its.auth')->prefix('donor')->group(function () {
    Route::get('/dashboard', [DonorDashboardController::class, 'index']);
    // Future donor routes can be added here
});
