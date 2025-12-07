<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class SupplierRepository extends EloquentBaseRepository implements SupplierRepositoryInterface
{
    public function __construct(Supplier $model)
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
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('tax_number', 'like', '%'.$search.'%');
            });
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function createForBranch(int $branchId, array $data): Supplier
    {
        $data['branch_id'] = $branchId;

        /** @var Supplier $supplier */
        $supplier = $this->create($data);

        return $supplier;
    }
}
