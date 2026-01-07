<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Query Optimization Service
 *
 * Provides helper methods for optimizing database queries,
 * preventing N+1 problems, and implementing caching strategies.
 */
class QueryOptimizationService
{
    /**
     * Default cache TTL in seconds (1 hour)
     */
    private const DEFAULT_CACHE_TTL = 3600;

    /**
     * Execute a query with caching
     */
    public function cachedQuery(string $cacheKey, \Closure $query, int $ttl = self::DEFAULT_CACHE_TTL): mixed
    {
        return Cache::remember($cacheKey, $ttl, $query);
    }

    /**
     * Execute a query with eager loading
     */
    public function withEagerLoading(Builder $query, array $relations): Builder
    {
        return $query->with($relations);
    }

    /**
     * Prevent N+1 by loading counts
     */
    public function withCounts(Builder $query, array $relations): Builder
    {
        return $query->withCount($relations);
    }

    /**
     * Chunk large datasets for processing
     */
    public function chunkProcess(Builder $query, int $chunkSize, \Closure $callback): void
    {
        $query->chunk($chunkSize, function (Collection $items) use ($callback) {
            foreach ($items as $item) {
                $callback($item);
            }
        });
    }

    /**
     * Get paginated results with optimal settings
     */
    public function paginateOptimized(Builder $query, int $perPage = 15, array $with = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        if (! empty($with)) {
            $query->with($with);
        }

        return $query->paginate($perPage);
    }

    /**
     * Execute a query with index hints
     */
    public function withIndexHint(Builder $query, string $index, string $hint = 'USE'): Builder
    {
        $table = $query->getModel()->getTable();

        return $query->from(DB::raw("{$table} {$hint} INDEX ({$index})"));
    }

    /**
     * Get distinct values efficiently
     */
    public function distinctValues(string $table, string $column, ?string $where = null): array
    {
        $query = DB::table($table)->distinct()->pluck($column);

        return $query->toArray();
    }

    /**
     * Batch insert with optimal chunk size
     */
    public function batchInsert(string $table, array $data, int $chunkSize = 500): int
    {
        $totalInserted = 0;
        $chunks = array_chunk($data, $chunkSize);

        DB::transaction(function () use ($table, $chunks, &$totalInserted) {
            foreach ($chunks as $chunk) {
                DB::table($table)->insert($chunk);
                $totalInserted += count($chunk);
            }
        });

        return $totalInserted;
    }

    /**
     * Batch update with optimal chunk size
     */
    public function batchUpdate(string $table, array $updates, string $keyColumn = 'id', int $chunkSize = 500): int
    {
        $totalUpdated = 0;
        $chunks = array_chunk($updates, $chunkSize, true);

        DB::transaction(function () use ($table, $chunks, $keyColumn, &$totalUpdated) {
            foreach ($chunks as $chunk) {
                foreach ($chunk as $key => $values) {
                    DB::table($table)
                        ->where($keyColumn, $key)
                        ->update($values);
                    $totalUpdated++;
                }
            }
        });

        return $totalUpdated;
    }

    /**
     * Get query execution time
     */
    public function measureQueryTime(\Closure $query): array
    {
        $startTime = microtime(true);

        $result = $query();

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        return [
            'result' => $result,
            'execution_time_ms' => round($executionTime, 2),
        ];
    }

    /**
     * Analyze slow queries
     */
    public function analyzeQuery(string $sql): array
    {
        $explain = DB::select("EXPLAIN {$sql}");

        return [
            'explain' => $explain,
            'recommendations' => $this->generateQueryRecommendations($explain),
        ];
    }

    /**
     * Generate query optimization recommendations
     */
    private function generateQueryRecommendations(array $explain): array
    {
        $recommendations = [];

        foreach ($explain as $row) {
            // Check for full table scan
            if (isset($row->type) && $row->type === 'ALL') {
                $recommendations[] = "Consider adding an index on table {$row->table}";
            }

            // Check for filesort
            if (isset($row->Extra) && str_contains($row->Extra, 'Using filesort')) {
                $recommendations[] = "Query requires filesort - consider adding an index for ORDER BY clause";
            }

            // Check for temporary table
            if (isset($row->Extra) && str_contains($row->Extra, 'Using temporary')) {
                $recommendations[] = "Query uses temporary table - consider optimizing GROUP BY or JOIN";
            }

            // Check for covering index
            if (isset($row->Extra) && ! str_contains($row->Extra, 'Using index')) {
                $recommendations[] = "Consider creating a covering index for better performance";
            }
        }

        if (empty($recommendations)) {
            $recommendations[] = "Query looks optimized!";
        }

        return $recommendations;
    }

    /**
     * Cache commonly accessed data
     */
    public function cacheCommonData(string $module): void
    {
        $cacheConfigs = [
            'products' => fn () => DB::table('products')
                ->where('status', 'active')
                ->select('id', 'name', 'sku', 'price')
                ->get(),

            'customers' => fn () => DB::table('customers')
                ->where('is_active', true)
                ->select('id', 'name', 'email', 'phone')
                ->get(),

            'categories' => fn () => DB::table('product_categories')
                ->where('status', 'active')
                ->select('id', 'name', 'parent_id')
                ->get(),

            'branches' => fn () => DB::table('branches')
                ->where('is_active', true)
                ->select('id', 'name', 'code')
                ->get(),
        ];

        if (isset($cacheConfigs[$module])) {
            $this->cachedQuery("common_data_{$module}", $cacheConfigs[$module], 7200);
        }
    }

    /**
     * Clear cache for a specific module
     */
    public function clearModuleCache(string $module): void
    {
        Cache::forget("common_data_{$module}");
    }

    /**
     * Get query statistics
     */
    public function getQueryStats(): array
    {
        $queries = DB::getQueryLog();

        $stats = [
            'total_queries' => count($queries),
            'total_time' => 0,
            'slowest_query' => null,
            'fastest_query' => null,
            'average_time' => 0,
        ];

        if (empty($queries)) {
            return $stats;
        }

        $times = array_column($queries, 'time');
        $stats['total_time'] = array_sum($times);
        $stats['average_time'] = $stats['total_time'] / count($queries);

        $slowestIndex = array_search(max($times), $times);
        $fastestIndex = array_search(min($times), $times);

        $stats['slowest_query'] = [
            'query' => $queries[$slowestIndex]['query'],
            'time' => $queries[$slowestIndex]['time'],
        ];

        $stats['fastest_query'] = [
            'query' => $queries[$fastestIndex]['query'],
            'time' => $queries[$fastestIndex]['time'],
        ];

        return $stats;
    }

    /**
     * Enable query logging for debugging
     */
    public function enableQueryLogging(): void
    {
        DB::enableQueryLog();
    }

    /**
     * Disable query logging
     */
    public function disableQueryLogging(): void
    {
        DB::disableQueryLog();
    }

    /**
     * Get query log
     */
    public function getQueryLog(): array
    {
        return DB::getQueryLog();
    }

    /**
     * Optimize table (MySQL specific)
     */
    public function optimizeTable(string $table): bool
    {
        try {
            DB::statement("OPTIMIZE TABLE {$table}");

            return true;
        } catch (\Exception $e) {
            logger()->error("Failed to optimize table {$table}: " . $e->getMessage());

            return false;
        }
    }

    /**
     * Analyze table (MySQL specific)
     */
    public function analyzeTable(string $table): bool
    {
        try {
            DB::statement("ANALYZE TABLE {$table}");

            return true;
        } catch (\Exception $e) {
            logger()->error("Failed to analyze table {$table}: " . $e->getMessage());

            return false;
        }
    }
}
