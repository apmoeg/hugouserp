<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStockHelpersTest extends TestCase
{
    use RefreshDatabase;

    public function test_reserve_stock_throws_when_product_missing(): void
    {
        $product = new Product;

        $this->expectException(\RuntimeException::class);
        $product->reserveStock(1);
    }

    public function test_release_stock_throws_when_product_missing(): void
    {
        $product = new Product;

        $this->expectException(\RuntimeException::class);
        $product->releaseStock(1);
    }
}
