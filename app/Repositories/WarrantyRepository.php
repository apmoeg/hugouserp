<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Warranty;
use App\Repositories\Contracts\WarrantyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class WarrantyRepository extends EloquentBaseRepository implements WarrantyRepositoryInterface
{
    public function __construct(Warranty $model)
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
