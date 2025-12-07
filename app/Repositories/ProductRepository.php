<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class ProductRepository extends EloquentBaseRepository implements ProductRepositoryInterface
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    protected function baseBranchQuery(int $branchId): Builder
    {
        return $this->query()->where('branch_id', $branchId);
    }

    public function paginateForBranch(int $branchId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->baseBranchQuery($branchId);

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $query->where(function (Builder $qq) use ($q): void {
                $qq->where('name', 'like', '%'.$q.'%')
                    ->orWhere('sku', 'like', '%'.$q.'%')
                    ->orWhere('barcode', 'like', '%'.$q.'%');
            });
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function createForBranch(int $branchId, array $data): Product
    {
        $data['branch_id'] = $branchId;

        /** @var Product $product */
        $product = $this->create($data);

        return $product;
    }
}
