<?php

use App\Http\Controllers\Files\UploadController;
use Illuminate\Support\Facades\Route;

// Files — تحت api-core & api-auth من المجموعة الأب في api.php
Route::prefix('files')->group(function () {
    Route::post('upload', [UploadController::class, 'upload'])->middleware('perm:files.upload');
    Route::get('{fileId}/meta', [UploadController::class, 'meta'])
        ->middleware('perm:files.view')
        ->where('fileId', '[A-Za-z0-9_.\\-\\/]+');
    Route::get('{fileId}', [UploadController::class, 'show'])
        ->middleware('perm:files.view')
        ->where('fileId', '[A-Za-z0-9_.\\-\\/]+');
    Route::delete('{fileId}', [UploadController::class, 'delete'])
        ->middleware('perm:files.delete')
        ->where('fileId', '[A-Za-z0-9_.\\-\\/]+');
});
