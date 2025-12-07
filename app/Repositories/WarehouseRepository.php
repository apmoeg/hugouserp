<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Warehouse;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class WarehouseRepository extends EloquentBaseRepository implements WarehouseRepositoryInterface
{
    public function __construct(Warehouse $model)
    {
        parent::__construct($model);
    }

    protected function baseBranchQuery(int $branchId): Builder
    {
        return $this->query()->where('branch_id', $branchId);
    }

    public function paginateForBranch(int $branchId, ?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->baseBranchQuery($branchId);

        if ($search !== null && $search !== '') {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function createForBranch(int $branchId, array $data): Warehouse
    {
        $data['branch_id'] = $branchId;

        /** @var Warehouse $warehouse */
        $warehouse = $this->create($data);

        return $warehouse;
    }
}
