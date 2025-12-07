<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\StockMovement;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class StockMovementRepository extends EloquentBaseRepository implements StockMovementRepositoryInterface
{
    public function __construct(StockMovement $model)
    {
        parent::__construct($model);
    }

    protected function baseBranchQuery(int $branchId): Builder
    {
        return $this->query()
            ->where('branch_id', $branchId);
    }

    public function paginateForBranch(int $branchId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->baseBranchQuery($branchId);

        if (! empty($filters['product_id'])) {
            $query->where('product_id', (int) $filters['product_id']);
        }

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', (int) $filters['warehouse_id']);
        }

        if (! empty($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function movementsForProduct(int $branchId, int $productId, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $filters['product_id'] = $productId;

        return $this->paginateForBranch($branchId, $filters, $perPage);
    }

    public function summaryForProduct(int $branchId, int $productId): array
    {
        $baseQuery = $this->baseBranchQuery($branchId)->where('product_id', $productId);

        $in = (float) (clone $baseQuery)->where('direction', 'in')->sum('qty');
        $out = (float) (clone $baseQuery)->where('direction', 'out')->sum('qty');

        return [
            'in' => $in,
            'out' => $out,
            'net' => $in - $out,
        ];
    }

    public function currentStockForBranch(int $branchId, int $productId): float
    {
        $baseQuery = $this->baseBranchQuery($branchId)->where('product_id', $productId);

        $in = (float) (clone $baseQuery)->where('direction', 'in')->sum('qty');
        $out = (float) (clone $baseQuery)->where('direction', 'out')->sum('qty');

        return $in - $out;
    }

    public function currentStockPerWarehouse(int $branchId, int $productId): Collection
    {
        $movements = $this->baseBranchQuery($branchId)
            ->where('product_id', $productId)
            ->get(['warehouse_id', 'direction', 'qty']);

        $map = $movements->groupBy('warehouse_id')
            ->map(function ($group) {
                $in = $group->where('direction', 'in')->sum('qty');
                $out = $group->where('direction', 'out')->sum('qty');

                return (float) ($in - $out);
            });

        return $map;
    }
}
