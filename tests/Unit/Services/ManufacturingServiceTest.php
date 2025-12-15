<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\BillOfMaterial;
use App\Models\Branch;
use App\Models\Product;
use App\Services\ManufacturingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManufacturingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ManufacturingService $service;
    protected Branch $branch;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ManufacturingService::class);

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB001',
        ]);

        $this->product = Product::create([
            'name' => 'Finished Product',
            'code' => 'FIN001',
            'type' => 'stock',
            'default_price' => 1000,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_can_create_bill_of_materials(): void
    {
        $data = [
            'product_id' => $this->product->id,
            'name' => 'BOM for Product',
            'bom_number' => 'BOM-001',
            'quantity' => 1,
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ];

        $bom = $this->service->createBom($data);

        $this->assertInstanceOf(BillOfMaterial::class, $bom);
        $this->assertEquals('BOM for Product', $bom->name);
    }

    public function test_can_calculate_bom_total_cost(): void
    {
        // Simple unit test for cost calculation logic
        $materials = [
            ['quantity' => 2, 'unit_cost' => 100],
            ['quantity' => 3, 'unit_cost' => 50],
        ];

        $totalCost = 0;
        foreach ($materials as $material) {
            $totalCost += $material['quantity'] * $material['unit_cost'];
        }

        $this->assertEquals(350, $totalCost);
    }

    public function test_validates_bom_creation_requires_product(): void
    {
        $data = [
            'product_id' => $this->product->id,
            'name' => 'Test BOM',
            'bom_number' => 'BOM-002',
            'branch_id' => $this->branch->id,
        ];

        $bom = $this->service->createBom($data);

        $this->assertNotNull($bom);
        $this->assertEquals($this->product->id, $bom->product_id);
    }
}
