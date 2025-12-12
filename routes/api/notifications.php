<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

// Notifications — تحت api-core & api-auth من المجموعة الأب
Route::prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->middleware('perm:notifications.view');
    Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->middleware('perm:notifications.view');
    Route::post('{id}/read', [NotificationController::class, 'markRead'])->middleware('perm:notifications.update');
    Route::post('/mark-many', [NotificationController::class, 'markMany'])->middleware('perm:notifications.update');
    Route::post('/mark-all', [NotificationController::class, 'markAll'])->middleware('perm:notifications.update');
});
