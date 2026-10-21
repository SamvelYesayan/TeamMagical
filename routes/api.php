<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeadlineActionController;
use App\Http\Controllers\Api\DeadlineController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/server-time', [AuthController::class, 'serverTime']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('auth.login');

// Mobile app (React Native): returns a bearer token instead of a cookie session.
Route::post('/mobile/login', [AuthController::class, 'mobileLogin'])->middleware('throttle:login')->name('auth.mobile_login');

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    // --- Account -----------------------------------------------------------------
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('me.show');
    Route::put('/me', [AuthController::class, 'updateProfile'])->name('me.update');
    Route::put('/me/password', [AuthController::class, 'changePassword'])->name('me.password');

    Route::get('/dashboard', DashboardController::class);

    // --- Lookups available to everyone ---------------------------------------------
    Route::get('/users/options', [UserController::class, 'options']);
    Route::get('/projects', [ProjectController::class, 'index']);

    // --- Deadlines (read: everyone; every write is authorised by DeadlinePolicy) ----
    Route::get('/deadlines', [DeadlineController::class, 'index']);
    Route::get('/deadlines/{deadline}', [DeadlineController::class, 'show']);

    Route::post('/deadlines/{deadline}/confirm', [DeadlineActionController::class, 'confirm']);
    Route::post('/deadlines/{deadline}/start', [DeadlineActionController::class, 'start']);
    Route::post('/deadlines/{deadline}/change-requests', [DeadlineActionController::class, 'requestChange']);
    Route::post('/deadlines/{deadline}/submissions', [DeadlineActionController::class, 'submit'])->middleware('throttle:uploads');

    // --- Files: streamed only after an authorisation check -------------------------
    Route::get('/deadline-attachments/{attachment}/download', [FileController::class, 'deadlineAttachment']);
    Route::get('/submission-files/{file}/download', [FileController::class, 'submissionFile']);
    Route::get('/announcements/{announcement}/image', [FileController::class, 'announcementImage']);
    Route::get('/announcement-attachments/{attachment}/download', [FileController::class, 'announcementAttachment']);

    // --- Announcements (read) -------------------------------------------------------
    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::get('/announcements/{announcement}', [AnnouncementController::class, 'show']);

    // --- Notifications (own only) ---------------------------------------------------
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->whereNumber('id');

    // =============================== ADMIN ONLY ===================================
    Route::middleware('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::post('/users/{user}/disable', [UserController::class, 'disable']);
        Route::post('/users/{user}/enable', [UserController::class, 'enable']);
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);

        Route::post('/projects', [ProjectController::class, 'store']);
        Route::put('/projects/{project}', [ProjectController::class, 'update']);
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);

        Route::post('/deadlines', [DeadlineController::class, 'store'])->middleware('throttle:uploads');
        Route::put('/deadlines/{deadline}', [DeadlineController::class, 'update']);
        Route::delete('/deadlines/{deadline}', [DeadlineController::class, 'destroy']);
        Route::post('/deadlines/{deadline}/attachments', [DeadlineActionController::class, 'addAttachments'])->middleware('throttle:uploads');
        Route::delete('/deadline-attachments/{attachment}', [DeadlineActionController::class, 'removeAttachment']);
        Route::post('/deadlines/{deadline}/start-review', [DeadlineActionController::class, 'startReview']);
        Route::post('/change-requests/{changeRequest}/decision', [DeadlineActionController::class, 'decideChange']);
        Route::post('/submissions/{submission}/review', [DeadlineActionController::class, 'review']);

        Route::post('/announcements', [AnnouncementController::class, 'store'])->middleware('throttle:uploads');
        Route::post('/announcements/{announcement}/update', [AnnouncementController::class, 'update'])->middleware('throttle:uploads');
        Route::post('/announcements/{announcement}/publish', [AnnouncementController::class, 'publish']);
        Route::post('/announcements/{announcement}/unpublish', [AnnouncementController::class, 'unpublish']);
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy']);

        Route::get('/activity-logs', [ActivityLogController::class, 'index']);
        Route::get('/settings', [SettingController::class, 'show']);
        Route::put('/settings', [SettingController::class, 'update']);
    });
});
