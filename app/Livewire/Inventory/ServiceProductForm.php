<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use App\Models\Module;
use App\Models\Product;
use App\Models\Tax;
use Livewire\Component;

class ServiceProductForm extends Component
{
    public ?int $productId = null;

    public ?int $moduleId = null;

    public string $name = '';

    public string $code = '';

    public string $sku = '';

    public ?string $description = null;

    public float $defaultPrice = 0;

    public float $cost = 0;

    public ?float $hourlyRate = null;

    public ?int $serviceDuration = null;

    public string $durationUnit = 'hours';

    public ?int $taxId = null;

    public string $status = 'active';

    public string $notes = '';

    public bool $showModal = false;

    protected $listeners = [
        'openServiceForm' => 'open',
        'editService' => 'edit',
    ];

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'sku' => 'nullable|string|max:50',
            'defaultPrice' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'hourlyRate' => 'nullable|numeric|min:0',
            'serviceDuration' => 'nullable|integer|min:1',
            'durationUnit' => 'required|in:minutes,hours,days',
            'taxId' => 'nullable|exists:taxes,id',
            'status' => 'required|in:active,inactive',
        ];
    }

    public function render()
    {
        $modules = Module::where('is_service', true)
            ->orWhere('key', 'services')
            ->orderBy('name')
            ->get();

        $taxes = Tax::where('is_active', true)->orderBy('name')->get();

        return view('livewire.inventory.service-product-form', [
            'modules' => $modules,
            'taxes' => $taxes,
        ]);
    }

    public function open(?int $moduleId = null): void
    {
        $this->resetForm();
        $this->moduleId = $moduleId;
        $this->showModal = true;
    }

    public function edit(int $productId): void
    {
        $product = Product::find($productId);
        if ($product) {
            $this->productId = $product->id;
            $this->moduleId = $product->module_id;
            $this->name = $product->name;
            $this->code = $product->code ?? '';
            $this->sku = $product->sku ?? '';
            $this->defaultPrice = (float) $product->default_price;
            $this->cost = (float) ($product->cost ?: $product->standard_cost);
            $this->hourlyRate = $product->hourly_rate;
            $this->serviceDuration = $product->service_duration;
            $this->durationUnit = $product->duration_unit ?? 'hours';
            $this->taxId = $product->tax_id;
            $this->status = $product->status;
            $this->notes = $product->notes ?? '';
            $this->showModal = true;
        }
    }

    public function resetForm(): void
    {
        $this->productId = null;
        $this->name = '';
        $this->code = '';
        $this->sku = '';
        $this->description = null;
        $this->defaultPrice = 0;
        $this->cost = 0;
        $this->hourlyRate = null;
        $this->serviceDuration = null;
        $this->durationUnit = 'hours';
        $this->taxId = null;
        $this->status = 'active';
        $this->notes = '';
    }

    public function close(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'code' => $this->code ?: null,
            'sku' => $this->sku ?: null,
            'module_id' => $this->moduleId,
            'type' => 'service',
            'product_type' => 'service',
            'default_price' => $this->defaultPrice,
            'standard_cost' => $this->cost,
            'cost' => $this->cost,
            'hourly_rate' => $this->hourlyRate,
            'service_duration' => $this->serviceDuration,
            'duration_unit' => $this->durationUnit,
            'tax_id' => $this->taxId,
            'status' => $this->status,
            'notes' => $this->notes ?: null,
            'branch_id' => auth()->user()->branch_id ?? 1,
            'is_serialized' => false,
            'is_batch_tracked' => false,
        ];

        if ($this->productId) {
            $product = Product::find($this->productId);
            $product->update($data);
            $this->dispatch('notify', type: 'success', message: __('Service updated successfully'));
        } else {
            $data['created_by'] = auth()->id();
            Product::create($data);
            $this->dispatch('notify', type: 'success', message: __('Service created successfully'));
        }

        $this->dispatch('serviceUpdated');
        $this->close();
    }

    public function calculateFromHourly(): void
    {
        if ($this->hourlyRate && $this->serviceDuration) {
            $hours = match ($this->durationUnit) {
                'minutes' => bcdiv((string) $this->serviceDuration, '60', 4),
                'hours' => (string) $this->serviceDuration,
                'days' => bcmul((string) $this->serviceDuration, '8', 4),
                default => (string) $this->serviceDuration,
            };
            $calculated = bcmul((string) $this->hourlyRate, $hours, 4);
            $this->defaultPrice = (float) bcdiv($calculated, '1', 2);
        }
    }
}
