<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Support\Str;

/**
 * Common validation patterns and data sanitization for models
 *
 * This trait provides consistent validation rules and data sanitization
 * across all models to ensure data integrity and security.
 */
trait ValidatesAndSanitizes
{
    /**
     * Boot the trait
     */
    protected static function bootValidatesAndSanitizes(): void
    {
        static::saving(function ($model) {
            $model->sanitizeAttributes();
        });
    }

    /**
     * Sanitize model attributes before saving
     */
    protected function sanitizeAttributes(): void
    {
        foreach ($this->attributes as $key => $value) {
            if (is_string($value)) {
                // Trim whitespace
                $this->attributes[$key] = trim($value);

                // Sanitize email fields
                if (Str::contains($key, 'email') && ! empty($value)) {
                    $this->attributes[$key] = strtolower($value);
                }

                // Sanitize phone fields (remove spaces, dashes)
                if (Str::contains($key, ['phone', 'mobile', 'fax']) && ! empty($value)) {
                    $this->attributes[$key] = preg_replace('/[\s\-\(\)]/', '', $value);
                }

                // Remove empty strings and convert to null for nullable fields
                if ($value === '' && $this->isNullable($key)) {
                    $this->attributes[$key] = null;
                }
            }
        }
    }

    /**
     * Check if a field is nullable based on fillable/casts
     */
    protected function isNullable(string $field): bool
    {
        // Check if explicitly marked as nullable in casts
        $casts = $this->getCasts();
        if (isset($casts[$field])) {
            $cast = $casts[$field];
            if (is_string($cast) && Str::contains($cast, 'nullable')) {
                return true;
            }
        }

        // Common nullable fields
        $nullableFields = [
            'notes', 'description', 'address', 'website',
            'shipping_address', 'billing_address', 'internal_notes',
            'customer_notes', 'terms_conditions', 'block_reason',
        ];

        return in_array($field, $nullableFields, true);
    }

    /**
     * Common validation rules for email
     */
    protected function emailRules(bool $required = false): array
    {
        $rules = ['email:rfc,dns', 'max:255'];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Common validation rules for phone numbers
     */
    protected function phoneRules(bool $required = false): array
    {
        $rules = ['string', 'max:50', 'regex:/^[\d\s\-\+\(\)]+$/'];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Common validation rules for currency amounts
     */
    protected function moneyRules(bool $required = false, float $min = 0): array
    {
        $rules = ['numeric', "min:{$min}", 'regex:/^\d+(\.\d{1,4})?$/'];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Common validation rules for percentage
     */
    protected function percentageRules(bool $required = false): array
    {
        $rules = ['numeric', 'min:0', 'max:100', 'regex:/^\d+(\.\d{1,2})?$/'];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Common validation rules for URLs
     */
    protected function urlRules(bool $required = false): array
    {
        $rules = ['url:http,https', 'max:500'];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Common validation rules for text fields with Unicode support
     */
    protected function textRules(bool $required = false, int $max = 65535): array
    {
        $rules = ['string', "max:{$max}"];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Common validation rules for dates
     */
    protected function dateRules(bool $required = false, ?string $after = null, ?string $before = null): array
    {
        $rules = ['date'];

        if ($after) {
            $rules[] = "after:{$after}";
        }

        if ($before) {
            $rules[] = "before:{$before}";
        }

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Common validation rules for SKU/Code fields
     */
    protected function codeRules(bool $required = false, bool $unique = false): array
    {
        $rules = ['string', 'max:100', 'alpha_dash'];

        if ($unique) {
            $table = $this->getTable();
            $rules[] = "unique:{$table},code," . ($this->exists ? $this->id : 'NULL') . ',id';
        }

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }
}
