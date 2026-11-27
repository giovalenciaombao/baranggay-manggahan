<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\StaffController;
use App\Http\Controllers\Api\Admin\AssistLogController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\ProfilePhotoController;

// --- Public Routes ---
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/auth/login',    [AuthController::class, 'login'])->middleware('throttle:10,1');

// Public alerts (visible on homepage)
Route::get('/alerts',         [AlertController::class, 'index']);
Route::get('/alerts/{alert}', [AlertController::class, 'show']);

// Google OAuth
Route::get('/auth/google/redirect',  [GoogleAuthController::class, 'redirect']);
Route::get('/auth/google/callback',  [GoogleAuthController::class, 'callback']);

// --- Authenticated Routes ---
Route::middleware('auth:sanctum')->group(function () {

    // Auth: available to any logged-in user (including pending ones)
    Route::post('/auth/logout',  [AuthController::class, 'logout']);
    Route::get('/auth/me',       [AuthController::class, 'me']);
    Route::put('/auth/profile',  [AuthController::class, 'updateProfile']);
    Route::put('/auth/password', [AuthController::class, 'updatePassword']);
    Route::post('/auth/proof',   [AuthController::class, 'uploadProof']);

    // Everything below requires an ACTIVE (approved) account
    Route::middleware('active')->group(function () {
        Route::get('/auth/profile/photo', [ProfilePhotoController::class, 'show']);
        Route::post('/auth/profile/photo', [ProfilePhotoController::class, 'store']);

        // Resident Dashboard
        Route::get('/resident/dashboard', [DashboardController::class, 'residentDashboard']);

        // Incidents
        Route::get('/incidents',            [IncidentController::class, 'index']);
        Route::post('/incidents',           [IncidentController::class, 'store']);
        Route::get('/incidents/{incident}', [IncidentController::class, 'show']);
        Route::get('/incidents/{incident}/photo', [IncidentController::class, 'photo']);

        // Appointments (availability must come before the {appointment} wildcard)
        Route::get('/appointments',               [AppointmentController::class, 'index']);
        Route::get('/appointments/availability',  [AppointmentController::class, 'availability']);
        Route::post('/appointments',              [AppointmentController::class, 'store']);
        Route::get('/appointments/{appointment}', [AppointmentController::class, 'show']);

        // Notifications
        Route::get('/notifications',              [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::put('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/notifications/read-all',     [NotificationController::class, 'markAllAsRead']);

        // --- Admin Routes ---
        Route::middleware('admin')->group(function () {

            // Admin Dashboard
            Route::get('/admin/dashboard', [DashboardController::class, 'index']);

            // User Management
            Route::get('/admin/users',               [UserController::class, 'index']);
            Route::post('/admin/users/bulk-approve', [UserController::class, 'bulkApprove']);
            Route::put('/admin/users/{user}/status', [UserController::class, 'updateStatus']);
            Route::get('/admin/users/{user}/proof',  [UserController::class, 'proof']);

            // Admin - view all records
            Route::get('/admin/incidents',    [IncidentController::class,    'adminIndex']);
            Route::get('/admin/appointments', [AppointmentController::class, 'adminIndex']);

            Route::get('/admin/incidents/{incident}/photo', [IncidentController::class, 'photo']);

            // Incidents - admin update
            Route::put('/incidents/{incident}',       [IncidentController::class,    'update']);

            // Appointments - admin update
            Route::put('/appointments/{appointment}', [AppointmentController::class, 'update']);

            // Alerts - admin CRUD
            Route::post('/alerts',            [AlertController::class, 'store']);
            Route::put('/alerts/{alert}',     [AlertController::class, 'update']);
            Route::delete('/alerts/{alert}',  [AlertController::class, 'destroy']);

            // Reports
            Route::get('/admin/reports/{type}', [ReportController::class, 'index']);

            // --- Super admin only: staff accounts + audit trail ---
            Route::middleware('super_admin')->group(function () {
                Route::get('/admin/staff',                    [StaffController::class, 'index']);
                Route::post('/admin/staff',                   [StaffController::class, 'store']);
                Route::put('/admin/staff/{user}/status',      [StaffController::class, 'updateStatus']);
                Route::put('/admin/staff/{user}/password',    [StaffController::class, 'resetPassword']);

                Route::get('/admin/assist-logs',              [AssistLogController::class, 'index']);
            });
        });
    });
});
