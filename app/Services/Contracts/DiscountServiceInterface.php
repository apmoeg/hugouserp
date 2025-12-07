<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface DiscountServiceInterface
{
    /**
     * Normalize discount value using optional caps and mode (percent/amount).
     */
    public function sanitize(float $value, bool $asPercent = true, ?float $cap = null): float;

    /**
     * Calculate discount total for a line.
     */
    public function lineTotal(float $qty, float $price, float $discount, bool $percent = true): float;
}
