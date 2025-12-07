<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface extends BaseRepositoryInterface
{
    public function paginateForBranch(int $branchId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function createForBranch(int $branchId, array $data);
}
