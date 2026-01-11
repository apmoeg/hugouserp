<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory;

    protected $table = 'system_settings';

    /**
     * Generic key/value settings storage.
     *
     * - key      : unique identifier (e.g. "app.name", "pos.receipt.footer")
     * - value    : json value
     * - type     : optional type hint (string,int,bool,array,json,encrypted,...)
     * - group    : logical group (e.g. "app","mail","pos","hr")
     * - is_public: whether it can be exposed to clients without auth
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'category',
        'is_public',
        'is_encrypted',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'value' => 'array',
        'is_public' => 'bool',
        'is_encrypted' => 'bool',
    ];

    /** Scopes */
    public function scopeKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }

    public function scopeGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    /**
     * Get a setting value by group and key.
     *
     * @param  string|null  $group  The setting group (null for any group)
     * @param  string  $key  The setting key
     * @param  mixed  $default  Default value if setting doesn't exist
     * @return mixed The setting value or default
     */
    public static function getValue(?string $group, string $key, $default = null): mixed
    {
        $query = static::where('key', $key);

        if ($group !== null) {
            $query->where('group', $group);
        }

        $setting = $query->first();

        if (! $setting) {
            return $default;
        }

        $value = $setting->value;

        // Handle array values - unwrap single-value arrays for non-array types
        if (is_array($value) && ! in_array($setting->type, ['array', 'json'], true)) {
            $value = count($value) === 1 ? $value[0] : $value;
        }

        // Cast based on type
        return match ($setting->type) {
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int' => (int) $value,
            'float', 'decimal' => (float) $value,
            default => $value,
        };
    }

    /**
     * Get a setting value using cache.
     */
    public static function cachedValue(?string $group, string $key, $default = null, int $ttlSeconds = 1800)
    {
        $cacheKey = sprintf('system_setting:%s:%s', $group ?? 'global', $key);

        return Cache::remember($cacheKey, $ttlSeconds, function () use ($group, $key, $default) {
            return static::getValue($group, $key, $default);
        });
    }
}
