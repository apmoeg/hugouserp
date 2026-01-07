<?php

declare(strict_types=1);

namespace App\Http\Requests\Traits;

/**
 * Reusable validation rules for payment terms
 * Eliminates code duplication across Customer and Supplier request classes
 */
trait HasPaymentTermsValidation
{
    /**
     * Get payment terms validation rules
     */
    protected function paymentTermsRules(bool $required = false): array
    {
        $base = ['nullable', 'string', 'in:immediate,net15,net30,net60,net90'];

        if ($required) {
            $base[0] = 'required';
        }

        return ['payment_terms' => $base];
    }

    /**
     * Get payment terms days validation rules
     */
    protected function paymentTermsDaysRules(bool $required = false): array
    {
        $base = ['nullable', 'integer', 'min:0', 'max:365'];

        if ($required) {
            $base[0] = 'required';
        }

        return ['payment_terms_days' => $base];
    }

    /**
     * Get payment due days validation rules
     */
    protected function paymentDueDaysRules(bool $required = false): array
    {
        $base = ['nullable', 'integer', 'min:0', 'max:365'];

        if ($required) {
            $base[0] = 'required';
        }

        return ['payment_due_days' => $base];
    }
}
