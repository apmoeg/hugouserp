<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\SaleItem;
use App\Repositories\Contracts\SaleItemRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class SaleItemRepository extends EloquentBaseRepository implements SaleItemRepositoryInterface
{
    public function __construct(SaleItem $model)
    {
        parent::__construct($model);
    }

    protected function baseBranchQuery(int $branchId): Builder
    {
        return $this->query()->where('branch_id', $branchId);
    }

    public function paginateForBranch(int $branchId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->baseBranchQuery($branchId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
