<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

/**
 * Value object for handling percentage values
 */
final class Percentage
{
    public function __construct(
        public readonly float $value,
    ) {
        if ($this->value < 0 || $this->value > 100) {
            throw new InvalidArgumentException('Percentage must be between 0 and 100');
        }
    }

    /**
     * Create Percentage instance from decimal (0.15 = 15%)
     */
    public static function fromDecimal(float $decimal): self
    {
        return new self($decimal * 100);
    }

    /**
     * Apply percentage to a Money amount
     */
    public function apply(Money $amount): Money
    {
        $discount = bcmul($amount->amount, (string) ($this->value / 100), 2);

        return new Money($discount, $amount->currency);
    }

    /**
     * Calculate the result after applying this percentage discount
     */
    public function applyDiscount(Money $amount): Money
    {
        return $amount->subtract($this->apply($amount));
    }

    /**
     * Get as decimal (15% = 0.15)
     */
    public function toDecimal(): float
    {
        return $this->value / 100;
    }

    /**
     * Format the percentage for display
     */
    public function format(int $decimals = 2): string
    {
        return number_format($this->value, $decimals).'%';
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * Check if percentage is zero
     */
    public function isZero(): bool
    {
        return $this->value === 0.0;
    }

    /**
     * Check if percentage is 100%
     */
    public function isFull(): bool
    {
        return $this->value === 100.0;
    }
}
