<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Product;

interface PricingServiceInterface
{
    /**
     * Resolve unit price for a product based on price-group and optional override.
     */
    public function resolveUnitPrice(Product $product, ?int $priceGroupId = null, ?float $override = null): float;

    /**
     * Calculate line totals (subtotal / discount / tax / total).
     *
     * Expected payload:
     *  - qty:      float
     *  - price:    float
     *  - discount: float|null
     *  - percent:  bool|null  (true => discount is percent, false => absolute)
     *  - tax_id:   int|null
     *
     * @param  array{qty:float, price:float, discount?:float, percent?:bool, tax_id?:int}  $line
     * @return array{subtotal:float, discount:float, tax:float, total:float}
     */
    public function lineTotals(array $line): array;
}
