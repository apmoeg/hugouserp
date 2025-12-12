<?php

declare(strict_types=1);

namespace App\Models;

/**
 * ChartOfAccount - Legacy compatibility alias for Account model
 * 
 * Provides backward compatibility by mapping legacy attribute names:
 * - account_code <-> account_number
 * - account_name <-> name
 * - account_type <-> type
 */
class ChartOfAccount extends Account
{
    protected $table = 'accounts';

    protected $fillable = [
        'branch_id',
        'account_code',
        'account_number',
        'account_name',
        'name',
        'name_ar',
        'account_type',
        'type',
        'currency_code',
        'requires_currency',
        'account_category',
        'sub_category',
        'parent_id',
        'balance',
        'is_active',
        'is_system_account',
        'description',
        'metadata',
    ];

    /**
     * Get account_code (legacy alias for account_number)
     */
    public function getAccountCodeAttribute(): ?string
    {
        return $this->attributes['account_number'] ?? null;
    }

    /**
     * Set account_code (legacy alias for account_number)
     */
    public function setAccountCodeAttribute(?string $value): void
    {
        $this->attributes['account_number'] = $value;
    }

    /**
     * Get account_name (legacy alias for name)
     */
    public function getAccountNameAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    /**
     * Set account_name (legacy alias for name)
     */
    public function setAccountNameAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
    }

    /**
     * Get account_type (legacy alias for type)
     */
    public function getAccountTypeAttribute(): ?string
    {
        return $this->attributes['type'] ?? null;
    }

    /**
     * Set account_type (legacy alias for type)
     */
    public function setAccountTypeAttribute(?string $value): void
    {
        $this->attributes['type'] = $value;
    }
}
