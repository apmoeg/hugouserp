<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SaleItemRepositoryInterface extends BaseRepositoryInterface
{
    public function paginateForBranch(int $branchId, int $perPage = 20): LengthAwarePaginator;
}
