<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Products;

use App\Livewire\Concerns\HandlesErrors;
use App\Models\Module;
use App\Models\Product;
use App\Models\ProductFieldValue;
use App\Services\ModuleProductService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;
    use HandlesErrors;

    public ?int $productId = null;

    public ?int $selectedModuleId = null;

    public array $form = [
        'name' => '',
        'sku' => '',
        'barcode' => '',
        'price' => 0.0,
        'cost' => 0.0,
        'price_currency' => 'EGP',
        'cost_currency' => 'EGP',
        'status' => 'active',
        'type' => 'stock',
        'branch_id' => 0,
        'module_id' => null,
    ];

    public array $currencies = [
        'EGP' => 'Egyptian Pound (EGP)',
        'USD' => 'US Dollar (USD)',
        'EUR' => 'Euro (EUR)',
        'SAR' => 'Saudi Riyal (SAR)',
        'AED' => 'UAE Dirham (AED)',
    ];

    public array $dynamicSchema = [];

    public array $dynamicData = [];

    protected ModuleProductService $moduleProductService;

    public function boot(ModuleProductService $moduleProductService): void
    {
        $this->moduleProductService = $moduleProductService;
    }

    public function mount(?int $product = null): void
    {
        $this->authorize('inventory.products.view');

        $user = Auth::user();
        $this->productId = $product;
        $this->form['branch_id'] = (int) ($user?->branch_id ?? 1);

        if ($this->productId) {
            $p = Product::with(['fieldValues.field'])->findOrFail($this->productId);

            $this->form['name'] = (string) $p->name;
            $this->form['sku'] = $p->sku ?? '';
            $this->form['barcode'] = $p->barcode ?? '';
            $this->form['price'] = (float) ($p->default_price ?? $p->price ?? 0);
            $this->form['cost'] = (float) ($p->standard_cost ?? $p->cost ?? 0);
            $this->form['price_currency'] = $p->price_currency ?? 'EGP';
            $this->form['cost_currency'] = $p->cost_currency ?? 'EGP';
            $this->form['status'] = (string) ($p->status ?? 'active');
            $this->form['type'] = (string) ($p->type ?? 'stock');
            $this->form['branch_id'] = (int) ($p->branch_id ?? $this->form['branch_id']);
            $this->form['module_id'] = $p->module_id;
            $this->selectedModuleId = $p->module_id;

            if ($p->module_id) {
                $this->loadModuleFields($p->module_id);

                foreach ($p->fieldValues as $fv) {
                    if ($fv->field) {
                        $this->dynamicData[$fv->field->field_key] = $fv->field_value;
                    }
                }
            }

            $legacyData = (array) ($p->extra_attributes ?? []);
            $this->dynamicData = array_merge($legacyData, $this->dynamicData);
        }
    }

    public function updatedSelectedModuleId($value): void
    {
        $this->form['module_id'] = $value ? (int) $value : null;

        if ($value) {
            $this->loadModuleFields((int) $value);
            $module = Module::find($value);
            if ($module) {
                $this->form['type'] = $module->is_service ? 'service' : 'stock';
            }
        } else {
            $this->dynamicSchema = [];
            $this->dynamicData = [];
        }
    }

    protected function loadModuleFields(int $moduleId): void
    {
        $fields = $this->moduleProductService->getModuleFields($moduleId, true);

        $this->dynamicSchema = $fields->map(function ($field) {
            return [
                'id' => $field->id,
                'key' => $field->field_key,
                'name' => $field->field_key,
                'label' => app()->getLocale() === 'ar' && $field->field_label_ar
                    ? $field->field_label_ar
                    : $field->field_label,
                'type' => $this->mapFieldType($field->field_type),
                'options' => $field->field_options ?? [],
                'required' => $field->is_required,
                'placeholder' => app()->getLocale() === 'ar' && $field->placeholder_ar
                    ? $field->placeholder_ar
                    : $field->placeholder,
                'default' => $field->default_value,
                'validation' => $field->validation_rules,
                'group' => $field->field_group,
            ];
        })->toArray();

        foreach ($this->dynamicSchema as $field) {
            if (! isset($this->dynamicData[$field['key']])) {
                $this->dynamicData[$field['key']] = $field['default'] ?? null;
            }
        }
    }

    protected function mapFieldType(string $type): string
    {
        return match ($type) {
            'textarea' => 'textarea',
            'number', 'decimal' => 'number',
            'date' => 'date',
            'datetime' => 'datetime-local',
            'select' => 'select',
            'multiselect' => 'multiselect',
            'checkbox' => 'checkbox',
            'radio' => 'radio',
            'file' => 'file',
            'image' => 'file',
            'color' => 'color',
            'url' => 'url',
            'email' => 'email',
            'phone' => 'tel',
            default => 'text',
        };
    }

    protected function rules(): array
    {
        $id = $this->productId;

        $rules = [
            'form.name' => ['required', 'string', 'max:255'],
            'form.sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($id),
            ],
            'form.barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'barcode')->ignore($id),
            ],
            'form.price' => ['required', 'numeric', 'min:0'],
            'form.cost' => ['nullable', 'numeric', 'min:0'],
            'form.price_currency' => ['required', 'string', Rule::in(['EGP', 'USD', 'EUR', 'SAR', 'AED', 'GBP', 'KWD'])],
            'form.cost_currency' => ['required', 'string', Rule::in(['EGP', 'USD', 'EUR', 'SAR', 'AED', 'GBP', 'KWD'])],
            'form.status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'form.type' => ['required', 'string', Rule::in(['stock', 'service'])],
            'form.branch_id' => ['required', 'integer'],
            'form.module_id' => ['nullable', 'integer', 'exists:modules,id'],
        ];

        foreach ($this->dynamicSchema as $field) {
            $fieldRules = [];

            if ($field['required']) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            if (! empty($field['validation'])) {
                $fieldRules = array_merge($fieldRules, explode('|', $field['validation']));
            }

            $rules["dynamicData.{$field['key']}"] = $fieldRules;
        }

        return $rules;
    }

    #[On('dynamic-form-updated')]
    public function handleDynamicFormUpdated(array $data): void
    {
        $this->dynamicData = $data;
    }

    public function save(): void
    {
        $this->validate();

        DB::transaction(function () {
            if ($this->productId) {
                $product = Product::findOrFail($this->productId);
            } else {
                $product = new Product;
            }

            $product->name = $this->form['name'];
            $product->sku = $this->form['sku'] ?: null;
            $product->barcode = $this->form['barcode'] ?: null;
            $product->default_price = $this->form['price'];
            $product->standard_cost = $this->form['cost'] ?? 0;
            $product->price_currency = $this->form['price_currency'];
            $product->cost_currency = $this->form['cost_currency'];
            $product->status = $this->form['status'];
            $product->type = $this->form['type'];
            $product->branch_id = $this->form['branch_id'];
            $product->module_id = $this->form['module_id'];
            $product->extra_attributes = $this->dynamicData;

            if (Auth::check()) {
                $userId = Auth::id();
                if (! $this->productId) {
                    $product->created_by = $userId;
                }
                $product->updated_by = $userId;
            }

            $product->save();

            if ($this->form['module_id'] && ! empty($this->dynamicData)) {
                ProductFieldValue::where('product_id', $product->id)->delete();

                $fields = $this->moduleProductService->getModuleFields((int) $this->form['module_id'], true);

                foreach ($fields as $field) {
                    $value = $this->dynamicData[$field->field_key] ?? null;

                    if ($value !== null && $value !== '') {
                        ProductFieldValue::create([
                            'product_id' => $product->id,
                            'module_product_field_id' => $field->id,
                            'field_value' => is_array($value) ? json_encode($value) : (string) $value,
                        ]);
                    }
                }
            }
        });

        session()->flash('status', $this->productId
            ? __('Product updated successfully.')
            : __('Product created successfully.')
        );

        $this->redirectRoute('inventory.products.index', navigate: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();
        $branchId = $user?->branch_id;

        $modules = collect();

        if ($branchId) {
            $enabledModuleIds = \App\Models\BranchModule::where('branch_id', $branchId)
                ->where('enabled', true)
                ->pluck('module_id')
                ->toArray();

            if (! empty($enabledModuleIds)) {
                $modules = Module::where('is_active', true)
                    ->where(function ($q) {
                        $q->where('has_inventory', true)
                            ->orWhere('is_service', true);
                    })
                    ->whereIn('id', $enabledModuleIds)
                    ->orderBy('sort_order')
                    ->get();
            }
        } else {
            $modules = Module::where('is_active', true)
                ->where(function ($q) {
                    $q->where('has_inventory', true)
                        ->orWhere('is_service', true);
                })
                ->orderBy('sort_order')
                ->get();
        }

        return view('livewire.inventory.products.form', [
            'modules' => $modules,
        ]);
    }
}
