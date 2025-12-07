<?php

namespace App\Services;

use App\Models\Module;
use App\Models\ModuleProductField;
use App\Models\Product;
use App\Models\ProductFieldValue;
use App\Models\ProductPriceTier;
use App\Traits\HandlesServiceErrors;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ModuleProductService
{
    use HandlesServiceErrors;

    public function getModuleFields(int $moduleId, bool $activeOnly = true): Collection
    {
        return $this->handleServiceOperation(
            callback: function () use ($moduleId, $activeOnly) {
                $query = ModuleProductField::where('module_id', $moduleId)
                    ->orderBy('sort_order')
                    ->orderBy('field_label');

                if ($activeOnly) {
                    $query->where('is_active', true);
                }

                return $query->get();
            },
            operation: 'getModuleFields',
            context: ['module_id' => $moduleId, 'active_only' => $activeOnly]
        );
    }

    public function getFieldsForForm(int $moduleId): Collection
    {
        return $this->handleServiceOperation(
            callback: function () use ($moduleId) {
                return ModuleProductField::where('module_id', $moduleId)
                    ->where('is_active', true)
                    ->where('show_in_form', true)
                    ->orderBy('sort_order')
                    ->get();
            },
            operation: 'getFieldsForForm',
            context: ['module_id' => $moduleId]
        );
    }

    public function getFieldsForList(int $moduleId): Collection
    {
        return $this->handleServiceOperation(
            callback: function () use ($moduleId) {
                return ModuleProductField::where('module_id', $moduleId)
                    ->where('is_active', true)
                    ->where('show_in_list', true)
                    ->orderBy('sort_order')
                    ->get();
            },
            operation: 'getFieldsForList',
            context: ['module_id' => $moduleId]
        );
    }

    public function getSearchableFields(int $moduleId): Collection
    {
        return $this->handleServiceOperation(
            callback: function () use ($moduleId) {
                return ModuleProductField::where('module_id', $moduleId)
                    ->where('is_active', true)
                    ->where('is_searchable', true)
                    ->get();
            },
            operation: 'getSearchableFields',
            context: ['module_id' => $moduleId]
        );
    }

    public function getFilterableFields(int $moduleId): Collection
    {
        return $this->handleServiceOperation(
            callback: function () use ($moduleId) {
                return ModuleProductField::where('module_id', $moduleId)
                    ->where('is_active', true)
                    ->where('is_filterable', true)
                    ->get();
            },
            operation: 'getFilterableFields',
            context: ['module_id' => $moduleId]
        );
    }

    public function createField(int $moduleId, array $data): ModuleProductField
    {
        return $this->handleServiceOperation(
            callback: function () use ($moduleId, $data) {
                $data['module_id'] = $moduleId;

                if (! isset($data['sort_order'])) {
                    $maxOrder = ModuleProductField::where('module_id', $moduleId)->max('sort_order') ?? 0;
                    $data['sort_order'] = $maxOrder + 1;
                }

                return ModuleProductField::create($data);
            },
            operation: 'createField',
            context: ['module_id' => $moduleId]
        );
    }

    public function updateField(int $fieldId, array $data): ModuleProductField
    {
        return $this->handleServiceOperation(
            callback: function () use ($fieldId, $data) {
                $field = ModuleProductField::findOrFail($fieldId);
                $field->update($data);

                return $field->fresh();
            },
            operation: 'updateField',
            context: ['field_id' => $fieldId]
        );
    }

    public function deleteField(int $fieldId): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($fieldId) {
                $field = ModuleProductField::findOrFail($fieldId);
                ProductFieldValue::where('module_product_field_id', $fieldId)->delete();

                return $field->delete();
            },
            operation: 'deleteField',
            context: ['field_id' => $fieldId],
            defaultValue: false
        );
    }

    public function reorderFields(int $moduleId, array $fieldIds): void
    {
        $this->handleServiceOperation(
            callback: function () use ($moduleId, $fieldIds) {
                foreach ($fieldIds as $index => $fieldId) {
                    ModuleProductField::where('id', $fieldId)
                        ->where('module_id', $moduleId)
                        ->update(['sort_order' => $index + 1]);
                }
            },
            operation: 'reorderFields',
            context: ['module_id' => $moduleId]
        );
    }

    public function createProduct(int $moduleId, array $productData, array $fieldValues = [], ?int $branchId = null): Product
    {
        return $this->handleServiceOperation(
            callback: function () use ($moduleId, $productData, $fieldValues, $branchId) {
                return DB::transaction(function () use ($moduleId, $productData, $fieldValues, $branchId) {
                    $module = Module::findOrFail($moduleId);

                    $productData['module_id'] = $moduleId;
                    $productData['branch_id'] = $branchId;

                    if ($module->is_rental) {
                        $productData['product_type'] = 'rental';
                    } elseif ($module->is_service) {
                        $productData['product_type'] = 'service';
                    }

                    $product = Product::create($productData);

                    foreach ($fieldValues as $fieldKey => $value) {
                        $product->setFieldValue($fieldKey, $value);
                    }

                    return $product->fresh(['fieldValues.field', 'module']);
                });
            },
            operation: 'createProduct',
            context: ['module_id' => $moduleId, 'branch_id' => $branchId]
        );
    }

    public function updateProduct(int $productId, array $productData, array $fieldValues = []): Product
    {
        return $this->handleServiceOperation(
            callback: function () use ($productId, $productData, $fieldValues) {
                return DB::transaction(function () use ($productId, $productData, $fieldValues) {
                    $product = Product::findOrFail($productId);
                    $product->update($productData);

                    foreach ($fieldValues as $fieldKey => $value) {
                        $product->setFieldValue($fieldKey, $value);
                    }

                    return $product->fresh(['fieldValues.field', 'module']);
                });
            },
            operation: 'updateProduct',
            context: ['product_id' => $productId]
        );
    }

    public function validateFieldValues(int $moduleId, array $fieldValues): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($moduleId, $fieldValues) {
                $fields = $this->getFieldsForForm($moduleId);
                $errors = [];

                foreach ($fields as $field) {
                    $value = $fieldValues[$field->field_key] ?? null;

                    if ($field->is_required && empty($value)) {
                        $errors[$field->field_key] = __('The :field field is required.', ['field' => $field->localized_label]);

                        continue;
                    }

                    if ($value === null) {
                        continue;
                    }

                    if ($field->validation_rules) {
                        $validator = Validator::make(
                            [$field->field_key => $value],
                            [$field->field_key => $field->validation_rules]
                        );

                        if ($validator->fails()) {
                            $errors[$field->field_key] = $validator->errors()->first($field->field_key);
                        }
                    }

                    if (in_array($field->field_type, ['select', 'radio']) && ! empty($field->field_options)) {
                        if (! array_key_exists($value, $field->field_options)) {
                            $errors[$field->field_key] = __('Invalid option selected for :field.', ['field' => $field->localized_label]);
                        }
                    }

                    if ($field->field_type === 'multiselect' && ! empty($field->field_options) && is_array($value)) {
                        foreach ($value as $v) {
                            if (! array_key_exists($v, $field->field_options)) {
                                $errors[$field->field_key] = __('Invalid option selected for :field.', ['field' => $field->localized_label]);
                                break;
                            }
                        }
                    }
                }

                return $errors;
            },
            operation: 'validateFieldValues',
            context: ['module_id' => $moduleId],
            defaultValue: []
        );
    }

    public function getProductsWithFields(int $moduleId, ?int $branchId = null, array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->handleServiceOperation(
            callback: function () use ($moduleId, $branchId, $filters) {
                $query = Product::where('module_id', $moduleId)
                    ->with(['fieldValues.field', 'module', 'branch'])
                    ->parentsOnly();

                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }

                foreach ($filters as $key => $value) {
                    if (empty($value)) {
                        continue;
                    }

                    if (in_array($key, ['name', 'code', 'sku'])) {
                        $query->where($key, 'like', "%{$value}%");
                    } elseif ($key === 'status') {
                        $query->where('status', $value);
                    } elseif ($key === 'custom_fields') {
                        foreach ($value as $fieldKey => $fieldValue) {
                            if (empty($fieldValue)) {
                                continue;
                            }

                            $query->whereHas('fieldValues', function ($q) use ($fieldKey, $fieldValue) {
                                $q->whereHas('field', fn ($fq) => $fq->where('field_key', $fieldKey))
                                    ->where('value', 'like', "%{$fieldValue}%");
                            });
                        }
                    }
                }

                return $query->orderBy('created_at', 'desc')->paginate(15);
            },
            operation: 'getProductsWithFields',
            context: ['module_id' => $moduleId, 'branch_id' => $branchId]
        );
    }

    public function getModulePricingInfo(int $moduleId): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($moduleId) {
                $module = Module::findOrFail($moduleId);

                return [
                    'pricing_type' => $module->pricing_type,
                    'has_buy_price' => $module->hasBuyPrice(),
                    'has_sell_price' => $module->hasSellPrice(),
                    'has_variations' => $module->has_variations,
                    'has_inventory' => $module->has_inventory,
                    'is_rental' => $module->is_rental,
                    'is_service' => $module->is_service,
                ];
            },
            operation: 'getModulePricingInfo',
            context: ['module_id' => $moduleId],
            defaultValue: []
        );
    }

    public function setPriceTier(int $productId, array $tierData): ProductPriceTier
    {
        return $this->handleServiceOperation(
            callback: function () use ($productId, $tierData) {
                return ProductPriceTier::updateOrCreate(
                    [
                        'product_id' => $productId,
                        'branch_id' => $tierData['branch_id'] ?? null,
                        'tier_name' => $tierData['tier_name'],
                    ],
                    $tierData
                );
            },
            operation: 'setPriceTier',
            context: ['product_id' => $productId]
        );
    }

    public function getPriceTiers(int $productId, ?int $branchId = null): Collection
    {
        return $this->handleServiceOperation(
            callback: function () use ($productId, $branchId) {
                $query = ProductPriceTier::where('product_id', $productId);

                if ($branchId) {
                    $query->forBranch($branchId);
                }

                return $query->orderBy('min_quantity')->get();
            },
            operation: 'getPriceTiers',
            context: ['product_id' => $productId, 'branch_id' => $branchId]
        );
    }

    public function duplicateModuleFields(int $sourceModuleId, int $targetModuleId): int
    {
        return $this->handleServiceOperation(
            callback: function () use ($sourceModuleId, $targetModuleId) {
                $fields = ModuleProductField::where('module_id', $sourceModuleId)->get();
                $count = 0;

                foreach ($fields as $field) {
                    $newField = $field->replicate();
                    $newField->module_id = $targetModuleId;
                    $newField->save();
                    $count++;
                }

                return $count;
            },
            operation: 'duplicateModuleFields',
            context: ['source_module_id' => $sourceModuleId, 'target_module_id' => $targetModuleId],
            defaultValue: 0
        );
    }
}
