<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\SparesServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\DB;

class SparesService implements SparesServiceInterface
{
    use HandlesServiceErrors;

    public function listCompatibility(int $productId): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($productId) {
                return DB::table('product_compatibility')
                    ->where('product_id', $productId)
                    ->orderByDesc('id')
                    ->get(['id', 'product_id', 'compatible_with_id'])
                    ->map(fn ($r) => ['id' => $r->id, 'product_id' => $r->product_id, 'compatible_with_id' => $r->compatible_with_id])
                    ->all();
            },
            operation: 'listCompatibility',
            context: ['product_id' => $productId],
            defaultValue: []
        );
    }

    public function attach(int $productId, int $compatibleWithId): int
    {
        return $this->handleServiceOperation(
            callback: function () use ($productId, $compatibleWithId) {
                return (int) DB::table('product_compatibility')->updateOrInsert(
                    ['product_id' => $productId, 'compatible_with_id' => $compatibleWithId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            },
            operation: 'attach',
            context: ['product_id' => $productId, 'compatible_with_id' => $compatibleWithId],
            defaultValue: 0
        );
    }

    public function detach(int $productId, int $compatibleWithId): int
    {
        return $this->handleServiceOperation(
            callback: function () use ($productId, $compatibleWithId) {
                return (int) DB::table('product_compatibility')
                    ->where('product_id', $productId)
                    ->where('compatible_with_id', $compatibleWithId)
                    ->delete();
            },
            operation: 'detach',
            context: ['product_id' => $productId, 'compatible_with_id' => $compatibleWithId],
            defaultValue: 0
        );
    }
}
