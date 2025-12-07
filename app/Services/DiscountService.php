<?php

declare(strict_types=1);

namespace App\Services;

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

                $cap = $cap ?? (
                    $asPercent
                        ? (float) config('pos.discount.max_percent', 20)
                        : (float) config('pos.discount.max_amount', 1000)
                );

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

                $discTotal = $percent
                    ? ($subtotal * ($discount / 100))
                    : $discount;

                $discTotal = min(max($discTotal, 0.0), $subtotal);

                return round($discTotal, 2);
            },
            operation: 'lineTotal',
            context: ['qty' => $qty, 'price' => $price, 'discount' => $discount, 'percent' => $percent],
            defaultValue: 0.0
        );
    }
}
