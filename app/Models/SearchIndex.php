<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SearchIndex extends BaseModel
{
    use HasFactory;

    protected $table = 'search_index';

    protected $fillable = [
        'branch_id',
        'searchable_type',
        'searchable_id',
        'title',
        'content',
        'module',
        'icon',
        'url',
        'metadata',
        'indexed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'indexed_at' => 'datetime',
    ];

    /**
     * Get the branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the searchable model.
     */
    public function searchable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Search across all indexed content.
     * Dynamically handles MySQL (MATCH AGAINST) and PostgreSQL (ILIKE/to_tsvector).
     */
    public static function search(string $query, int $branchId, array|string|null $module = null, int $limit = 20): array
    {
        $builder = static::query()->where('branch_id', $branchId);

        if ($module) {
            if (is_array($module)) {
                if (empty($module)) {
                    return [];
                }

                $builder->whereIn('module', $module);
            } else {
                $builder->where('module', $module);
            }
        }

        // Determine the database driver and apply appropriate search
        $driver = static::getDatabaseDriver();

        if ($driver === 'mysql' && static::hasFullTextIndex()) {
            // MySQL: Use MATCH AGAINST for full-text search
            $builder->whereRaw(
                'MATCH(title, content) AGAINST(? IN BOOLEAN MODE)',
                [$query]
            );
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: Use ILIKE for case-insensitive search
            // For better performance, consider using ts_vector if the column exists
            $searchTerm = '%'.mb_strtolower($query).'%';
            $builder->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(title) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(content) LIKE ?', [$searchTerm]);
            });
        } else {
            // Fallback to LIKE search for other drivers (SQLite, etc.)
            $searchTerm = '%'.$query.'%';
            $builder->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', $searchTerm)
                    ->orWhere('content', 'like', $searchTerm);
            });
        }

        return $builder->orderBy('indexed_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get the current database driver.
     */
    private static function getDatabaseDriver(): string
    {
        try {
            $connection = config('database.default');

            return config("database.connections.{$connection}.driver", 'mysql');
        } catch (\Exception $e) {
            return 'mysql';
        }
    }

    /**
     * Check if full-text index exists (MySQL specific).
     */
    private static function hasFullTextIndex(): bool
    {
        try {
            $driver = static::getDatabaseDriver();

            // Full-text MATCH AGAINST is MySQL-specific
            return $driver === 'mysql';
        } catch (\Exception $e) {
            return false;
        }
    }
}
