<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface StockMovementRepositoryInterface extends BaseRepositoryInterface
{
    public function paginateForBranch(int $branchId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function movementsForProduct(int $branchId, int $productId, array $filters = [], int $perPage = 50): LengthAwarePaginator;

    /**
     * @return array{in: float, out: float, net: float}
     */
    public function summaryForProduct(int $branchId, int $productId): array;

    public function currentStockForBranch(int $branchId, int $productId): float;

    /**
     * @return Collection<int, float> keyed by warehouse_id
     */
    public function currentStockPerWarehouse(int $branchId, int $productId): Collection;
}
