<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\StockMovement;

interface InventoryServiceInterface
{
    public function currentQty(int $productId, ?int $warehouseId = null): float;

    public function adjust(int $productId, float $qty, ?int $warehouseId = null, ?string $note = null): StockMovement;

    public function transfer(int $productId, float $qty, int $fromWarehouse, int $toWarehouse): array;
}
