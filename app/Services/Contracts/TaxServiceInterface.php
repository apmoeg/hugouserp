<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface TaxServiceInterface
{
    public function rate(?int $taxId): float;

    public function compute(float $base, ?int $taxId): float;

    /** New: tax amount considering inclusive/exclusive */
    public function amountFor(float $base, ?int $taxId): float;

    /** New: base + tax (or base if inclusive) */
    public function totalWithTax(float $base, ?int $taxId): float;
}
