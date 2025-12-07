<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

// Notifications — تحت api-core & api-auth من المجموعة الأب
Route::prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->middleware('perm:notifications.view');
    Route::post('{id}/read', [NotificationController::class, 'markAsRead'])->middleware('perm:notifications.update');
    Route::post('read-all', [NotificationController::class, 'markAllAsRead'])->middleware('perm:notifications.update');
    Route::delete('{id}', [NotificationController::class, 'destroy'])->middleware('perm:notifications.delete');
    Route::post('subscribe', [NotificationController::class, 'subscribe'])->middleware('perm:notifications.subscribe');
    Route::post('unsubscribe', [NotificationController::class, 'unsubscribe'])->middleware('perm:notifications.subscribe');
});
