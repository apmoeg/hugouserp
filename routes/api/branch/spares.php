<?php

use App\Http\Controllers\Branch\Spares\CompatibilityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Spares Module (under /api/v1/branches/{branch}/modules/spares)
| Parent groups already apply: api-core + api-auth + api-branch
| This file only enforces module.enabled and sets fine-grained permissions.
|--------------------------------------------------------------------------
*/

Route::prefix('modules/spares')->middleware(['module.enabled:spares'])->group(function () {

    // ==================== Compatibility ====================
    Route::get('compatibility', [CompatibilityController::class, 'index'])
        ->middleware('perm:spares.compatibility.view');

    Route::post('compatibility/attach', [CompatibilityController::class, 'attach'])
        ->middleware('perm:spares.compatibility.update');

    Route::post('compatibility/detach', [CompatibilityController::class, 'detach'])
        ->middleware('perm:spares.compatibility.update');
});
