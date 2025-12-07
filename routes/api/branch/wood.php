<?php

use App\Http\Controllers\Branch\Wood\ConversionController;
use App\Http\Controllers\Branch\Wood\WasteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Wood Module (under /api/v1/branches/{branch}/modules/wood)
| Parent group already applies: api-core + api-auth + api-branch
| Here we only enforce the module and attach fine-grained permissions.
|--------------------------------------------------------------------------
*/

Route::prefix('modules/wood')->middleware(['module.enabled:wood'])->group(function () {

    // ==================== Conversions ====================
    Route::get('conversions', [ConversionController::class, 'index'])
        ->middleware('perm:wood.conversions.view');

    Route::post('conversions', [ConversionController::class, 'store'])
        ->middleware('perm:wood.conversions.create');

    // إعادة احتساب التحويلات
    Route::post('conversions/recalc', [ConversionController::class, 'recalc'])
        ->middleware('perm:wood.conversions.update');

    // ==================== Waste ====================
    Route::get('waste', [WasteController::class, 'index'])
        ->middleware('perm:wood.waste.view');

    Route::post('waste', [WasteController::class, 'store'])
        ->middleware('perm:wood.waste.create');
});
