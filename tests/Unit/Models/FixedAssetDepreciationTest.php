<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\FixedAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixedAssetDepreciationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_numeric_attributes_prevent_null_arithmetic(): void
    {
        $asset = new FixedAsset();

        $this->assertEquals(0, $asset->purchase_cost);
        $this->assertEquals(0, $asset->salvage_value);
        $this->assertEquals(0, $asset->accumulated_depreciation);
        $this->assertEquals(0, $asset->book_value);
        $this->assertEquals(0, $asset->useful_life_years);
        $this->assertEquals(0, $asset->useful_life_months);
    }

    public function test_is_fully_depreciated_handles_null_values(): void
    {
        $asset = new FixedAsset([
            'name' => 'Test Asset',
            'purchase_cost' => null,
            'salvage_value' => null,
            'book_value' => null,
        ]);

        // Should not throw error with null values
        $this->assertTrue($asset->isFullyDepreciated());
    }

    public function test_is_fully_depreciated_returns_true_when_book_value_equals_salvage(): void
    {
        $asset = new FixedAsset([
            'name' => 'Test Asset',
            'purchase_cost' => 10000,
            'salvage_value' => 1000,
            'book_value' => 1000,
        ]);

        $this->assertTrue($asset->isFullyDepreciated());
    }

    public function test_is_fully_depreciated_returns_true_when_book_value_below_salvage(): void
    {
        $asset = new FixedAsset([
            'name' => 'Test Asset',
            'purchase_cost' => 10000,
            'salvage_value' => 1000,
            'book_value' => 500,
        ]);

        $this->assertTrue($asset->isFullyDepreciated());
    }

    public function test_is_fully_depreciated_returns_false_when_book_value_above_salvage(): void
    {
        $asset = new FixedAsset([
            'name' => 'Test Asset',
            'purchase_cost' => 10000,
            'salvage_value' => 1000,
            'book_value' => 5000,
        ]);

        $this->assertFalse($asset->isFullyDepreciated());
    }

    public function test_get_total_useful_life_months_handles_null_values(): void
    {
        $asset = new FixedAsset([
            'name' => 'Test Asset',
            'useful_life_years' => null,
            'useful_life_months' => null,
        ]);

        // Should not throw error with null values
        $this->assertEquals(0, $asset->getTotalUsefulLifeMonths());
    }

    public function test_get_total_useful_life_months_calculates_correctly(): void
    {
        $asset = new FixedAsset([
            'name' => 'Test Asset',
            'useful_life_years' => 5,
            'useful_life_months' => 6,
        ]);

        $this->assertEquals(66, $asset->getTotalUsefulLifeMonths());
    }

    public function test_get_total_useful_life_months_with_only_years(): void
    {
        $asset = new FixedAsset([
            'name' => 'Test Asset',
            'useful_life_years' => 3,
            'useful_life_months' => 0,
        ]);

        $this->assertEquals(36, $asset->getTotalUsefulLifeMonths());
    }

    public function test_get_total_useful_life_months_with_only_months(): void
    {
        $asset = new FixedAsset([
            'name' => 'Test Asset',
            'useful_life_years' => 0,
            'useful_life_months' => 18,
        ]);

        $this->assertEquals(18, $asset->getTotalUsefulLifeMonths());
    }

    public function test_get_monthly_depreciation_handles_null_values(): void
    {
        $asset = new FixedAsset([
            'name' => 'Test Asset',
            'purchase_cost' => null,
            'salvage_value' => null,
            'useful_life_years' => null,
            'useful_life_months' => null,
        ]);

        // Should not throw error with null values
        $this->assertEquals(0, $asset->getMonthlyDepreciation());
    }

    public function test_get_monthly_depreciation_calculates_correctly(): void
    {
        $asset = new FixedAsset([
            'name' => 'Test Asset',
            'purchase_cost' => 12000,
            'salvage_value' => 2000,
            'useful_life_years' => 5,
            'useful_life_months' => 0,
        ]);

        // (12000 - 2000) / 60 months = 166.67
        $this->assertEqualsWithDelta(166.67, $asset->getMonthlyDepreciation(), 0.01);
    }

    public function test_get_monthly_depreciation_returns_zero_when_no_useful_life(): void
    {
        $asset = new FixedAsset([
            'name' => 'Test Asset',
            'purchase_cost' => 10000,
            'salvage_value' => 1000,
            'useful_life_years' => 0,
            'useful_life_months' => 0,
        ]);

        $this->assertEquals(0, $asset->getMonthlyDepreciation());
    }

    public function test_get_monthly_depreciation_prevents_negative_when_salvage_exceeds_purchase(): void
    {
        $asset = new FixedAsset([
            'name' => 'Test Asset',
            'purchase_cost' => 5000,
            'salvage_value' => 8000, // Salvage > Purchase (edge case)
            'useful_life_years' => 5,
            'useful_life_months' => 0,
        ]);

        // Should return 0, not negative
        $this->assertEquals(0, $asset->getMonthlyDepreciation());
    }

    public function test_get_monthly_depreciation_with_zero_salvage_value(): void
    {
        $asset = new FixedAsset([
            'name' => 'Test Asset',
            'purchase_cost' => 12000,
            'salvage_value' => 0,
            'useful_life_years' => 4,
            'useful_life_months' => 0,
        ]);

        // 12000 / 48 months = 250
        $this->assertEquals(250, $asset->getMonthlyDepreciation());
    }

    public function test_get_monthly_depreciation_with_fractional_months(): void
    {
        $asset = new FixedAsset([
            'name' => 'Test Asset',
            'purchase_cost' => 10000,
            'salvage_value' => 1000,
            'useful_life_years' => 2,
            'useful_life_months' => 3,
        ]);

        // (10000 - 1000) / 27 months = 333.33...
        $this->assertEqualsWithDelta(333.33, $asset->getMonthlyDepreciation(), 0.01);
    }
}
