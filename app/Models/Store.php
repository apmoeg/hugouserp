<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'url',
        'branch_id',
        'is_active',
        'settings',
        'last_sync_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'last_sync_at' => 'datetime',
    ];

    public const TYPE_SHOPIFY = 'shopify';

    public const TYPE_WOOCOMMERCE = 'woocommerce';

    public const TYPE_LARAVEL = 'laravel';

    public const TYPE_CUSTOM = 'custom';

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function integration(): HasOne
    {
        return $this->hasOne(StoreIntegration::class);
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(StoreToken::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(StoreSyncLog::class);
    }

    public function productMappings(): HasMany
    {
        return $this->hasMany(ProductStoreMapping::class);
    }

    public function isShopify(): bool
    {
        return $this->type === self::TYPE_SHOPIFY;
    }

    public function isWooCommerce(): bool
    {
        return $this->type === self::TYPE_WOOCOMMERCE;
    }

    public function isLaravel(): bool
    {
        return $this->type === self::TYPE_LARAVEL;
    }

    public function generateApiToken(string $name, array $abilities = ['*']): StoreToken
    {
        return $this->tokens()->create([
            'name' => $name,
            'token' => bin2hex(random_bytes(32)),
            'abilities' => $abilities,
        ]);
    }
}
