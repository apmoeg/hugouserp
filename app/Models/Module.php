<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    use HasFactory;

    protected $table = 'modules';

    protected $fillable = [
        'key',
        'slug',
        'name',
        'name_ar',
        'version',
        'is_core',
        'is_active',
        'description',
        'description_ar',
        'icon',
        'color',
        'sort_order',
        'default_settings',
        'pricing_type',
        'has_variations',
        'has_inventory',
        'has_serial_numbers',
        'has_expiry_dates',
        'has_batch_numbers',
        'is_rental',
        'is_service',
        'category',
    ];

    protected $casts = [
        'is_core' => 'bool',
        'is_active' => 'bool',
        'has_variations' => 'bool',
        'has_inventory' => 'bool',
        'has_serial_numbers' => 'bool',
        'has_expiry_dates' => 'bool',
        'has_batch_numbers' => 'bool',
        'is_rental' => 'bool',
        'is_service' => 'bool',
        'default_settings' => 'array',
    ];

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_modules')
            ->using(BranchModule::class)
            ->withPivot(['enabled', 'settings', 'module_key'])
            ->withTimestamps();
    }

    public function branchModules(): HasMany
    {
        return $this->hasMany(BranchModule::class);
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(ModuleCustomField::class);
    }

    public function productFields(): HasMany
    {
        return $this->hasMany(ModuleProductField::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function rentalPeriods(): HasMany
    {
        return $this->hasMany(RentalPeriod::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(ModuleSetting::class);
    }

    public function reportDefinitions(): HasMany
    {
        return $this->hasMany(ReportDefinition::class);
    }

    public function getLocalizedNameAttribute(): string
    {
        return app()->getLocale() === 'ar' && $this->name_ar ? $this->name_ar : $this->name;
    }

    public function getLocalizedDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'ar' && $this->description_ar ? $this->description_ar : $this->description;
    }

    public function hasBuyPrice(): bool
    {
        return in_array($this->pricing_type, ['buy_sell', 'cost_only']);
    }

    public function hasSellPrice(): bool
    {
        return in_array($this->pricing_type, ['buy_sell', 'sell_only']);
    }

    public function getSetting(string $key, $branchId = null, $default = null)
    {
        return ModuleSetting::getValue($this->id, $key, $branchId, $default);
    }

    public function setSetting(string $key, $value, $branchId = null, $type = 'string'): ModuleSetting
    {
        return ModuleSetting::setValue($this->id, $key, $value, $branchId, $type);
    }

    public function scopeCore($q)
    {
        return $q->where('is_core', true);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeKey($q, string $key)
    {
        return $q->where('key', $key);
    }

    public function scopeSlug($q, string $slug)
    {
        return $q->where('slug', $slug);
    }

    public function scopeRental($q)
    {
        return $q->where('is_rental', true);
    }

    public function scopeService($q)
    {
        return $q->where('is_service', true);
    }

    public function scopeWithInventory($q)
    {
        return $q->where('has_inventory', true);
    }

    public function scopeCategory($q, string $category)
    {
        return $q->where('category', $category);
    }
}
