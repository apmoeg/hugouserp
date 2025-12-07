<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tax;
use App\Services\Contracts\TaxServiceInterface;
use App\Traits\HandlesServiceErrors;

class TaxService implements TaxServiceInterface
{
    use HandlesServiceErrors;

    public function rate(?int $taxId): float
    {
        if (! $taxId || ! class_exists(Tax::class)) {
            return 0.0;
        }
        $tax = Tax::find($taxId);

        return (float) ($tax->rate ?? 0.0);
    }

    public function compute(float $base, ?int $taxId): float
    {
        $r = $this->rate($taxId);

        return round($base * ($r / 100), 2);
    }

    public function amountFor(float $base, ?int $taxId): float
    {
        return $this->handleServiceOperation(
            callback: function () use ($base, $taxId) {
                if (! $taxId || ! class_exists(Tax::class)) {
                    return 0.0;
                }

                $tax = Tax::find($taxId);
                if (! $tax) {
                    return 0.0;
                }

                $rate = (float) $tax->rate;

                if ($rate <= 0) {
                    return 0.0;
                }

                if ($tax->is_inclusive ?? false) {
                    $taxPortion = $base - ($base / (1 + $rate / 100));

                    return round($taxPortion, 4);
                }

                $taxAmount = $base * $rate / 100;

                return round($taxAmount, 4);
            },
            operation: 'amountFor',
            context: ['base' => $base, 'tax_id' => $taxId],
            defaultValue: 0.0
        );
    }

    public function totalWithTax(float $base, ?int $taxId): float
    {
        return $this->handleServiceOperation(
            callback: function () use ($base, $taxId) {
                if (! $taxId || ! class_exists(Tax::class)) {
                    return round($base, 4);
                }

                $tax = Tax::find($taxId);
                if (! $tax) {
                    return round($base, 4);
                }

                if ($tax->is_inclusive ?? false) {
                    return round($base, 4);
                }

                return round($base + $this->amountFor($base, $taxId), 4);
            },
            operation: 'totalWithTax',
            context: ['base' => $base, 'tax_id' => $taxId],
            defaultValue: round($base, 4)
        );
    }
}
