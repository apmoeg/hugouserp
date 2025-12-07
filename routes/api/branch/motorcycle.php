<?php

use App\Http\Controllers\Branch\Motorcycle\ContractController;
use App\Http\Controllers\Branch\Motorcycle\VehicleController;
use App\Http\Controllers\Branch\Motorcycle\WarrantyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Motorcycle Module (under /api/v1/branches/{branch}/modules/motorcycle)
| Parent groups already apply: api-core + api-auth + api-branch
| This file enforces module.enabled and attaches fine-grained permissions.
|--------------------------------------------------------------------------
*/

Route::prefix('modules/motorcycle')->middleware(['module.enabled:motorcycle'])->group(function () {

    // ==================== Vehicles ====================
    Route::prefix('vehicles')->group(function () {
        Route::get('/', [VehicleController::class, 'index'])
            ->middleware('perm:motorcycle.vehicles.view');

        Route::post('/', [VehicleController::class, 'store'])
            ->middleware('perm:motorcycle.vehicles.create');

        Route::get('{vehicle}', [VehicleController::class, 'show'])
            ->middleware('perm:motorcycle.vehicles.view');

        Route::match(['put', 'patch'], '{vehicle}', [VehicleController::class, 'update'])
            ->middleware('perm:motorcycle.vehicles.update');
    });

    // ==================== Contracts ====================
    Route::prefix('contracts')->group(function () {
        Route::get('/', [ContractController::class, 'index'])
            ->middleware('perm:motorcycle.contracts.view');

        Route::post('/', [ContractController::class, 'store'])
            ->middleware('perm:motorcycle.contracts.create');

        Route::get('{contract}', [ContractController::class, 'show'])
            ->middleware('perm:motorcycle.contracts.view');

        Route::match(['put', 'patch'], '{contract}', [ContractController::class, 'update'])
            ->middleware('perm:motorcycle.contracts.update');

        Route::post('{contract}/deliver', [ContractController::class, 'deliver'])
            ->middleware('perm:motorcycle.contracts.deliver');
    });

    // ==================== Warranties ====================
    Route::prefix('warranties')->group(function () {
        Route::get('/', [WarrantyController::class, 'index'])
            ->middleware('perm:motorcycle.warranties.view');

        Route::post('/', [WarrantyController::class, 'store'])
            ->middleware('perm:motorcycle.warranties.create');

        Route::get('{warranty}', [WarrantyController::class, 'show'])
            ->middleware('perm:motorcycle.warranties.view');

        Route::match(['put', 'patch'], '{warranty}', [WarrantyController::class, 'update'])
            ->middleware('perm:motorcycle.warranties.update');
    });
});
