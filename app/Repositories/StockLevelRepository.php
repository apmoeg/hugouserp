<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\StockLevelRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;

final class StockLevelRepository implements StockLevelRepositoryInterface
{
    public function __construct(
        private readonly StockMovementRepositoryInterface $movements,
    ) {}

    public function getForProduct(int $branchId, int $productId): float
    {
        return $this->movements->currentStockForBranch($branchId, $productId);
    }
}
