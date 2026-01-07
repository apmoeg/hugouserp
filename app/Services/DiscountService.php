<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidDiscountException;
use App\Rules\ValidDiscount;
use App\Services\Contracts\DiscountServiceInterface;
use App\Traits\HandlesServiceErrors;

class DiscountService implements DiscountServiceInterface
{
    use HandlesServiceErrors;

    public function sanitize(float $value, bool $asPercent = true, ?float $cap = null): float
    {
        return $this->handleServiceOperation(
            callback: function () use ($value, $asPercent, $cap) {
                $value = max(0.0, $value);

                $cap = $cap ?? $this->getMaxDiscount($asPercent);

                // Validate discount against cap
                if ($value > $cap) {
                    throw new InvalidDiscountException(
                        $value,
                        $cap,
                        $asPercent ? 'percent' : 'amount'
                    );
                }

                if ($asPercent) {
                    $rule = ValidDiscount::percent($cap);
                } else {
                    $rule = ValidDiscount::amount($cap);
                }

                $rule->validate('discount', $value, function (string $message): void {});

                return min($value, $cap);
            },
            operation: 'sanitize',
            context: ['value' => $value, 'as_percent' => $asPercent, 'cap' => $cap],
            defaultValue: 0.0
        );
    }

    public function lineTotal(float $qty, float $price, float $discount, bool $percent = true): float
    {
        return $this->handleServiceOperation(
            callback: function () use ($qty, $price, $discount, $percent) {
                $qty = max(0.0, $qty);
                $price = max(0.0, $price);

                $subtotal = $qty * $price;

                $discount = $this->sanitize($discount, $percent);

                // Use bcmath for precise discount calculation
                $discTotal = $percent
                    ? bcmul((string) $subtotal, bcdiv((string) $discount, '100', 6), 6)
                    : (string) $discount;

                // Use bcmath comparisons for precision
                if (bccomp($discTotal, '0', 6) < 0) {
                    $discTotal = '0';
                } elseif (bccomp($discTotal, (string) $subtotal, 6) > 0) {
                    $discTotal = (string) $subtotal;
                }

                return (float) bcdiv($discTotal, '1', 2);
            },
            operation: 'lineTotal',
            context: ['qty' => $qty, 'price' => $price, 'discount' => $discount, 'percent' => $percent],
            defaultValue: 0.0
        );
    }

    /**
     * Get maximum allowed discount from configuration
     */
    protected function getMaxDiscount(bool $asPercent): float
    {
        if ($asPercent) {
            // Check sales config first, then fallback to POS config
            return (float) config('sales.max_line_discount_percent',
                config('pos.discount.max_percent', 50)
            );
        }

        return (float) config('pos.discount.max_amount', 1000);
    }

    /**
     * Validate invoice-level discount
     */
    public function validateInvoiceDiscount(float $discount, bool $asPercent = true): bool
    {
        $maxDiscount = $asPercent
            ? (float) config('sales.max_invoice_discount_percent', 30)
            : (float) config('pos.discount.max_amount', 1000);

        if ($discount > $maxDiscount) {
            throw new InvalidDiscountException(
                $discount,
                $maxDiscount,
                $asPercent ? 'percent' : 'amount'
            );
        }

        return true;
    }
}
