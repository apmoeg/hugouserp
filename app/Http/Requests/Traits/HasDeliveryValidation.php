<?php

declare(strict_types=1);

namespace App\Http\Requests\Traits;

/**
 * Reusable validation rules for delivery tracking
 * Eliminates code duplication across Sale and Purchase request classes
 */
trait HasDeliveryValidation
{
    /**
     * Get expected delivery date validation rules
     */
    protected function expectedDeliveryDateRules(bool $required = false): array
    {
        $base = ['nullable', 'date'];

        if ($required) {
            $base[0] = 'required';
        }

        return ['expected_delivery_date' => $base];
    }

    /**
     * Get actual delivery date validation rules
     */
    protected function actualDeliveryDateRules(bool $required = false): array
    {
        $base = ['nullable', 'date'];

        if ($required) {
            $base[0] = 'required';
        }

        return ['actual_delivery_date' => $base];
    }

    /**
     * Get delivery date validation rules (for sales)
     */
    protected function deliveryDateRules(bool $required = false): array
    {
        $base = ['nullable', 'date'];

        if ($required) {
            $base[0] = 'required';
        }

        return ['delivery_date' => $base];
    }

    /**
     * Get shipping method validation rules
     */
    protected function shippingMethodRules(bool $required = false): array
    {
        $base = ['nullable', 'string', 'max:191'];

        if ($required) {
            $base[0] = 'required';
        }

        return ['shipping_method' => $base];
    }

    /**
     * Get tracking number validation rules
     */
    protected function trackingNumberRules(bool $required = false): array
    {
        $base = ['nullable', 'string', 'max:191'];

        if ($required) {
            $base[0] = 'required';
        }

        return ['tracking_number' => $base];
    }

    /**
     * Get notes validation rules
     */
    protected function notesRules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
            'internal_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
