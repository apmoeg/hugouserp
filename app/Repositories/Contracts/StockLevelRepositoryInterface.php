<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

interface StockLevelRepositoryInterface
{
    public function getForProduct(int $branchId, int $productId): float;
}
