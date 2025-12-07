<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCompatibility;
use App\Models\VehicleModel;
use App\Traits\HandlesServiceErrors;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SparePartsService
{
    use HandlesServiceErrors;

    public function getVehicleModels(?string $brand = null, bool $activeOnly = true): Collection
    {
        return $this->handleServiceOperation(
            callback: function () use ($brand, $activeOnly) {
                $query = VehicleModel::query()
                    ->when($activeOnly, fn ($q) => $q->active())
                    ->when($brand, fn ($q) => $q->forBrand($brand))
                    ->orderBy('brand')
                    ->orderBy('model')
                    ->orderBy('year_from');

                return $query->get();
            },
            operation: 'getVehicleModels',
            context: ['brand' => $brand, 'active_only' => $activeOnly],
            defaultValue: new Collection
        );
    }

    public function getBrands(): array
    {
        return $this->handleServiceOperation(
            callback: fn () => VehicleModel::query()
                ->active()
                ->distinct()
                ->pluck('brand')
                ->sort()
                ->values()
                ->all(),
            operation: 'getBrands',
            context: [],
            defaultValue: []
        );
    }

    public function createVehicleModel(array $data): VehicleModel
    {
        return $this->handleServiceOperation(
            callback: fn () => VehicleModel::create($data),
            operation: 'createVehicleModel',
            context: ['data' => $data]
        );
    }

    public function updateVehicleModel(int $id, array $data): VehicleModel
    {
        return $this->handleServiceOperation(
            callback: function () use ($id, $data) {
                $model = VehicleModel::findOrFail($id);
                $model->update($data);

                return $model->fresh();
            },
            operation: 'updateVehicleModel',
            context: ['id' => $id, 'data' => $data]
        );
    }

    public function deleteVehicleModel(int $id): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($id) {
                $model = VehicleModel::findOrFail($id);

                return $model->delete();
            },
            operation: 'deleteVehicleModel',
            context: ['id' => $id],
            defaultValue: false
        );
    }

    public function getProductCompatibilities(int $productId): Collection
    {
        return $this->handleServiceOperation(
            callback: fn () => ProductCompatibility::with('vehicleModel')
                ->forProduct($productId)
                ->get(),
            operation: 'getProductCompatibilities',
            context: ['product_id' => $productId],
            defaultValue: new Collection
        );
    }

    public function getCompatibleProducts(int $vehicleModelId): Collection
    {
        return $this->handleServiceOperation(
            callback: fn () => ProductCompatibility::with('product')
                ->forVehicle($vehicleModelId)
                ->get(),
            operation: 'getCompatibleProducts',
            context: ['vehicle_model_id' => $vehicleModelId],
            defaultValue: new Collection
        );
    }

    public function addCompatibility(int $productId, int $vehicleModelId, array $data = []): ProductCompatibility
    {
        return $this->handleServiceOperation(
            callback: fn () => ProductCompatibility::updateOrCreate(
                [
                    'product_id' => $productId,
                    'vehicle_model_id' => $vehicleModelId,
                ],
                [
                    'oem_number' => $data['oem_number'] ?? null,
                    'position' => $data['position'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'is_verified' => $data['is_verified'] ?? false,
                ]
            ),
            operation: 'addCompatibility',
            context: ['product_id' => $productId, 'vehicle_model_id' => $vehicleModelId]
        );
    }

    public function removeCompatibility(int $productId, int $vehicleModelId): bool
    {
        return $this->handleServiceOperation(
            callback: fn () => ProductCompatibility::where('product_id', $productId)
                ->where('vehicle_model_id', $vehicleModelId)
                ->delete() > 0,
            operation: 'removeCompatibility',
            context: ['product_id' => $productId, 'vehicle_model_id' => $vehicleModelId],
            defaultValue: false
        );
    }

    public function bulkAddCompatibilities(int $productId, array $vehicleModelIds, array $commonData = []): int
    {
        return $this->handleServiceOperation(
            callback: function () use ($productId, $vehicleModelIds, $commonData) {
                $count = 0;

                DB::transaction(function () use ($productId, $vehicleModelIds, $commonData, &$count) {
                    foreach ($vehicleModelIds as $vehicleModelId) {
                        ProductCompatibility::updateOrCreate(
                            [
                                'product_id' => $productId,
                                'vehicle_model_id' => $vehicleModelId,
                            ],
                            [
                                'oem_number' => $commonData['oem_number'] ?? null,
                                'position' => $commonData['position'] ?? null,
                                'notes' => $commonData['notes'] ?? null,
                                'is_verified' => $commonData['is_verified'] ?? false,
                            ]
                        );
                        $count++;
                    }
                });

                return $count;
            },
            operation: 'bulkAddCompatibilities',
            context: ['product_id' => $productId, 'count' => count($vehicleModelIds)],
            defaultValue: 0
        );
    }

    public function searchByOem(string $oemNumber): Collection
    {
        return $this->handleServiceOperation(
            callback: fn () => ProductCompatibility::with(['product', 'vehicleModel'])
                ->byOem($oemNumber)
                ->get(),
            operation: 'searchByOem',
            context: ['oem_number' => $oemNumber],
            defaultValue: new Collection
        );
    }

    public function findCompatibleParts(string $brand, string $model, ?int $year = null): Collection
    {
        return $this->handleServiceOperation(
            callback: function () use ($brand, $model, $year) {
                $vehicleModels = VehicleModel::query()
                    ->active()
                    ->forBrand($brand)
                    ->where('model', $model)
                    ->when($year, fn ($q) => $q->forYear($year))
                    ->pluck('id');

                return Product::query()
                    ->whereHas('compatibilities', function ($q) use ($vehicleModels) {
                        $q->whereIn('vehicle_model_id', $vehicleModels);
                    })
                    ->with(['compatibilities' => function ($q) use ($vehicleModels) {
                        $q->whereIn('vehicle_model_id', $vehicleModels);
                    }])
                    ->get();
            },
            operation: 'findCompatibleParts',
            context: ['brand' => $brand, 'model' => $model, 'year' => $year],
            defaultValue: new Collection
        );
    }
}
