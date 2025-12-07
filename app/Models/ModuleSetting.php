<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class ModuleSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'branch_id',
        'setting_key',
        'setting_value',
        'setting_type',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getTypedValueAttribute()
    {
        return match ($this->setting_type) {
            'boolean' => filter_var($this->setting_value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->setting_value,
            'float', 'decimal' => (float) $this->setting_value,
            'array', 'json' => json_decode($this->setting_value, true),
            default => $this->setting_value,
        };
    }

    public function setTypedValueAttribute($value): void
    {
        $this->setting_value = is_array($value) ? json_encode($value) : (string) $value;
    }

    public function scopeForModule($query, $moduleId)
    {
        return $query->where('module_id', $moduleId);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)
                ->orWhereNull('branch_id');
        });
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('branch_id');
    }

    public static function getValue($moduleId, $key, $branchId = null, $default = null)
    {
        $query = static::where('module_id', $moduleId)
            ->where('setting_key', $key);

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })->orderByRaw('CASE WHEN branch_id IS NULL THEN 1 ELSE 0 END');
        } else {
            $query->whereNull('branch_id');
        }

        $setting = $query->first();

        return $setting ? $setting->typed_value : $default;
    }

    public static function setValue($moduleId, $key, $value, $branchId = null, $type = 'string'): static
    {
        return static::updateOrCreate(
            [
                'module_id' => $moduleId,
                'branch_id' => $branchId,
                'setting_key' => $key,
            ],
            [
                'setting_value' => is_array($value) ? json_encode($value) : (string) $value,
                'setting_type' => $type,
            ]
        );
    }

    public static function cachedValue(int $moduleId, string $key, ?int $branchId = null, $default = null, int $ttlSeconds = 1800)
    {
        $cacheKey = sprintf(
            'module_setting:%d:%s:%s',
            $moduleId,
            $key,
            $branchId ?? 'global'
        );

        return Cache::remember($cacheKey, $ttlSeconds, function () use ($moduleId, $key, $branchId, $default) {
            return static::getValue($moduleId, $key, $branchId, $default);
        });
    }
}
