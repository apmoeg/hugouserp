<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Get current stock for a product from stock_movements table
     * Compatible with MySQL 8.4, PostgreSQL, and SQLite
     */
    public static function getCurrentStock(int $productId, ?int $warehouseId = null): float
    {
        $query = DB::table('stock_movements')
            ->where('product_id', $productId);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        return (float) $query->selectRaw('COALESCE(SUM(CASE WHEN direction = ? THEN qty ELSE -qty END), 0) as stock', ['in'])
            ->value('stock');
    }

    /**
     * Get current stock for multiple products
     * Returns array keyed by product_id
     */
    public static function getBulkCurrentStock(array $productIds, ?int $warehouseId = null): array
    {
        $query = DB::table('stock_movements')
            ->whereIn('product_id', $productIds);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        $results = $query
            ->select('product_id')
            ->selectRaw('COALESCE(SUM(CASE WHEN direction = ? THEN qty ELSE -qty END), 0) as stock', ['in'])
            ->groupBy('product_id')
            ->get();

        return $results->pluck('stock', 'product_id')->toArray();
    }

    /**
     * Get stock value for a product from stock_movements table
     */
    public static function getStockValue(int $productId, ?int $warehouseId = null): float
    {
        $query = DB::table('stock_movements')
            ->where('product_id', $productId);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        return (float) $query->sum('valuated_amount');
    }

    /**
     * Get SQL expression for calculating current stock
     * Use this for SELECT queries that need to calculate stock on the fly
     */
    public static function getStockCalculationExpression(string $productIdColumn = 'products.id'): string
    {
        return "COALESCE((SELECT SUM(CASE WHEN direction = 'in' THEN qty ELSE -qty END) FROM stock_movements WHERE stock_movements.product_id = {$productIdColumn}), 0)";
    }

    /**
     * Get SQL expression for calculating stock in a specific warehouse
     */
    public static function getWarehouseStockCalculationExpression(string $productIdColumn = 'products.id', string $warehouseIdColumn = 'warehouses.id'): string
    {
        return "COALESCE((SELECT SUM(CASE WHEN direction = 'in' THEN qty ELSE -qty END) FROM stock_movements WHERE stock_movements.product_id = {$productIdColumn} AND stock_movements.warehouse_id = {$warehouseIdColumn}), 0)";
    }
}
