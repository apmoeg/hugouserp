<?php

use App\Http\Controllers\Files\UploadController;
use Illuminate\Support\Facades\Route;

// Files — تحت api-core & api-auth من المجموعة الأب في api.php
Route::prefix('files')->group(function () {
    Route::post('upload', [UploadController::class, 'upload'])->middleware('perm:files.upload');
    Route::delete('{fileId}', [UploadController::class, 'delete'])->middleware('perm:files.delete');
    Route::get('{fileId}', [UploadController::class, 'show'])->middleware('perm:files.view');
    Route::get('{fileId}/meta', [UploadController::class, 'meta'])->middleware('perm:files.view');
});
