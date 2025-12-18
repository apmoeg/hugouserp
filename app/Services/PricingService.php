<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PriceGroup;
use App\Models\Product;
use App\Services\Contracts\DiscountServiceInterface;
use App\Services\Contracts\PricingServiceInterface;
use App\Services\Contracts\TaxServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Arr;

class PricingService implements PricingServiceInterface
{
    use HandlesServiceErrors;

    public function __construct(
        protected DiscountServiceInterface $discounts,
        protected TaxServiceInterface $taxes
    ) {}

    public function resolveUnitPrice(Product $product, ?int $priceGroupId = null, ?float $override = null): float
    {
        return $this->handleServiceOperation(
            callback: function () use ($product, $priceGroupId, $override) {
                if ($override !== null) {
                    // Use bcmath for consistent precision
                    return (float) bcdiv((string) $override, '1', 4);
                }

                if ($priceGroupId && class_exists(PriceGroup::class)) {
                    $pg = PriceGroup::find($priceGroupId);
                    if ($pg && method_exists($pg, 'priceFor')) {
                        $p = $pg->priceFor($product->getKey());
                        if ($p !== null) {
                            return (float) bcdiv((string) $p, '1', 4);
                        }
                    }
                }

                $base = $product->default_price ?? 0.0;

                return (float) bcdiv((string) $base, '1', 4);
            },
            operation: 'resolveUnitPrice',
            context: ['product_id' => $product->id, 'price_group_id' => $priceGroupId],
            defaultValue: 0.0
        );
    }

    public function lineTotals(array $line): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($line) {
                $qty = max(0.0, (float) Arr::get($line, 'qty', 1));
                $price = max(0.0, (float) Arr::get($line, 'price', 0));
                $percent = (bool) Arr::get($line, 'percent', true);
                $discVal = (float) Arr::get($line, 'discount', 0);
                $taxId = Arr::get($line, 'tax_id');

                $subtotal = $qty * $price;

                $discount = $this->discounts->lineTotal($qty, $price, $discVal, $percent);
                $discount = min($discount, $subtotal);

                $baseAfterDiscount = max(0.0, $subtotal - $discount);

                $taxAmount = 0.0;
                if (! empty($taxId)) {
                    $taxAmount = $this->taxes->amountFor($baseAfterDiscount, (int) $taxId);
                }

                $total = ! empty($taxId)
                    ? $this->taxes->totalWithTax($baseAfterDiscount, (int) $taxId)
                    : $baseAfterDiscount;

                // Use bcmath for consistent precision across all line totals
                return [
                    'subtotal' => (float) bcdiv((string) $subtotal, '1', 2),
                    'discount' => (float) bcdiv((string) $discount, '1', 2),
                    'tax' => (float) bcdiv((string) $taxAmount, '1', 2),
                    'total' => (float) bcdiv((string) $total, '1', 2),
                ];
            },
            operation: 'lineTotals',
            context: ['line' => $line],
            defaultValue: ['subtotal' => 0.0, 'discount' => 0.0, 'tax' => 0.0, 'total' => 0.0]
        );
    }
}
