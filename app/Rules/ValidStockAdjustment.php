<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a stock adjustment payload:
 * - qty must be non-zero
 * - after adjustment, stock must not be negative (if current passed)
 */
class ValidStockAdjustment implements ValidationRule
{
    public function __construct(
        protected ?float $currentQty = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value)) {
            $fail('Adjustment quantity must be numeric.');

            return;
        }

        $qty = (float) $value;
        if (abs($qty) < 1e-9) {
            $fail('Adjustment quantity cannot be zero.');

            return;
        }

        if ($this->currentQty !== null && ($this->currentQty + $qty) < -1e-9) {
            $fail('Adjustment would result in negative stock.');
        }
    }
}
