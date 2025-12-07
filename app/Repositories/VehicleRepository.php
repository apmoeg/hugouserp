<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Vehicle;
use App\Repositories\Contracts\VehicleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class VehicleRepository extends EloquentBaseRepository implements VehicleRepositoryInterface
{
    public function __construct(Vehicle $model)
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
