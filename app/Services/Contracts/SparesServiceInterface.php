<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\ProductCompatibility;
use App\Models\VehicleModel;
use Illuminate\Database\Eloquent\Collection;

interface SparesServiceInterface
{
    public function getVehicleModels(?string $brand = null, bool $activeOnly = true): Collection;

    /** @return array<int,string> */
    public function getBrands(): array;

    public function createVehicleModel(array $data): VehicleModel;

    public function updateVehicleModel(int $id, array $data): VehicleModel;

    public function deleteVehicleModel(int $id): bool;

    public function getProductCompatibilities(int $productId): Collection;

    public function getCompatibleProducts(int $vehicleModelId): Collection;

    public function addCompatibility(int $productId, int $vehicleModelId, array $data = []): ProductCompatibility;

    public function removeCompatibility(int $productId, int $vehicleModelId): bool;

    public function bulkAddCompatibilities(int $productId, array $vehicleModelIds, array $commonData = []): int;

    public function searchByOem(string $oemNumber): Collection;

    public function findCompatibleParts(string $brand, string $model, ?int $year = null): Collection;

    // Backward compatibility methods
    public function listCompatibility(int $productId): array;

    public function attach(int $productId, int $compatibleWithId): int;

    public function detach(int $productId, int $compatibleWithId): int;
}
