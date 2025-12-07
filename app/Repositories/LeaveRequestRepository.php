<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\LeaveRequest;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class LeaveRequestRepository extends EloquentBaseRepository implements LeaveRequestRepositoryInterface
{
    public function __construct(LeaveRequest $model)
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
