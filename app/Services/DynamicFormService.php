<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ModuleField;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * DynamicFormService - Manages dynamic form fields for entities
 * 
 * PURPOSE: Allows branch managers to customize form fields without code changes
 * FEATURES:
 *   - Define custom fields per entity/module
 *   - Field types: text, number, date, select, checkbox, file, etc.
 *   - Field validation rules
 *   - Field visibility and ordering
 *   - Branch-specific field configurations
 */
class DynamicFormService
{
    use HandlesServiceErrors;

    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Available field types with their configurations
     */
    public const FIELD_TYPES = [
        'text' => [
            'label' => 'Text',
            'component' => 'input',
            'input_type' => 'text',
        ],
        'number' => [
            'label' => 'Number',
            'component' => 'input',
            'input_type' => 'number',
            'attributes' => ['step' => '0.01'],
        ],
        'email' => [
            'label' => 'Email',
            'component' => 'input',
            'input_type' => 'email',
        ],
        'tel' => [
            'label' => 'Phone',
            'component' => 'input',
            'input_type' => 'tel',
        ],
        'url' => [
            'label' => 'URL',
            'component' => 'input',
            'input_type' => 'url',
        ],
        'textarea' => [
            'label' => 'Text Area',
            'component' => 'textarea',
            'attributes' => ['rows' => 3],
        ],
        'date' => [
            'label' => 'Date',
            'component' => 'input',
            'input_type' => 'date',
        ],
        'datetime' => [
            'label' => 'Date & Time',
            'component' => 'input',
            'input_type' => 'datetime-local',
        ],
        'time' => [
            'label' => 'Time',
            'component' => 'input',
            'input_type' => 'time',
        ],
        'select' => [
            'label' => 'Dropdown',
            'component' => 'select',
        ],
        'checkbox' => [
            'label' => 'Checkbox',
            'component' => 'checkbox',
        ],
        'radio' => [
            'label' => 'Radio Buttons',
            'component' => 'radio',
        ],
        'file' => [
            'label' => 'File Upload',
            'component' => 'file',
        ],
        'image' => [
            'label' => 'Image Upload',
            'component' => 'file',
            'accept' => 'image/*',
        ],
        'money' => [
            'label' => 'Money/Currency',
            'component' => 'input',
            'input_type' => 'number',
            'attributes' => ['step' => '0.01', 'min' => '0'],
        ],
        'percentage' => [
            'label' => 'Percentage',
            'component' => 'input',
            'input_type' => 'number',
            'attributes' => ['step' => '0.01', 'min' => '0', 'max' => '100'],
        ],
        'color' => [
            'label' => 'Color Picker',
            'component' => 'input',
            'input_type' => 'color',
        ],
        'hidden' => [
            'label' => 'Hidden',
            'component' => 'input',
            'input_type' => 'hidden',
        ],
    ];

    /**
     * Get form fields for an entity
     */
    public function getFieldsForEntity(string $entity, ?string $moduleKey = null, ?int $branchId = null): Collection
    {
        return $this->handleServiceOperation(
            callback: function () use ($entity, $moduleKey, $branchId) {
                $cacheKey = "dynamic_fields:{$entity}:{$moduleKey}:{$branchId}";
                
                return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($entity, $moduleKey, $branchId) {
                    $query = ModuleField::query()
                        ->where('entity', $entity)
                        ->where('is_visible', true)
                        ->orderBy('sort_order');

                    if ($moduleKey) {
                        $query->whereHas('module', fn($q) => $q->where('key', $moduleKey));
                    }

                    if ($branchId) {
                        $query->where(function ($q) use ($branchId) {
                            $q->where('branch_id', $branchId)
                              ->orWhereNull('branch_id');
                        });
                    } else {
                        $query->whereNull('branch_id');
                    }

                    return $query->get();
                });
            },
            operation: 'getFieldsForEntity',
            context: ['entity' => $entity, 'module' => $moduleKey, 'branch_id' => $branchId],
            defaultValue: collect()
        );
    }

    /**
     * Build validation rules for entity fields
     */
    public function getValidationRules(string $entity, ?string $moduleKey = null, ?int $branchId = null): array
    {
        $fields = $this->getFieldsForEntity($entity, $moduleKey, $branchId);
        $rules = [];

        foreach ($fields as $field) {
            $fieldRules = [];

            if ($field->is_required) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            // Add type-specific rules
            switch ($field->field_type) {
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                case 'url':
                    $fieldRules[] = 'url';
                    break;
                case 'number':
                case 'money':
                    $fieldRules[] = 'numeric';
                    break;
                case 'percentage':
                    $fieldRules[] = 'numeric';
                    $fieldRules[] = 'min:0';
                    $fieldRules[] = 'max:100';
                    break;
                case 'date':
                case 'datetime':
                    $fieldRules[] = 'date';
                    break;
                case 'checkbox':
                    $fieldRules[] = 'boolean';
                    break;
                case 'select':
                case 'radio':
                    if ($field->options) {
                        $options = array_keys($field->options);
                        $fieldRules[] = 'in:' . implode(',', $options);
                    }
                    break;
                case 'file':
                case 'image':
                    $fieldRules[] = 'file';
                    if ($field->field_type === 'image') {
                        $fieldRules[] = 'image';
                    }
                    break;
            }

            // Add custom validation if defined
            if ($field->validation_rules) {
                if (is_array($field->validation_rules)) {
                    $fieldRules = array_merge($fieldRules, $field->validation_rules);
                } else {
                    $fieldRules[] = $field->validation_rules;
                }
            }

            $rules["extra.{$field->key}"] = $fieldRules;
        }

        return $rules;
    }

    /**
     * Get field configuration for form rendering
     */
    public function getFieldConfig(ModuleField $field): array
    {
        $typeConfig = self::FIELD_TYPES[$field->field_type] ?? self::FIELD_TYPES['text'];
        
        return [
            'key' => $field->key,
            'name' => "extra.{$field->key}",
            'label' => $field->label,
            'type' => $field->field_type,
            'component' => $typeConfig['component'],
            'input_type' => $typeConfig['input_type'] ?? null,
            'placeholder' => $field->placeholder,
            'help_text' => $field->help_text,
            'required' => $field->is_required,
            'disabled' => !$field->is_editable,
            'options' => $field->options,
            'default_value' => $field->default_value,
            'attributes' => array_merge(
                $typeConfig['attributes'] ?? [],
                $field->attributes ?? []
            ),
        ];
    }

    /**
     * Create a new dynamic field
     */
    public function createField(array $data): ModuleField
    {
        return $this->handleServiceOperation(
            callback: function () use ($data) {
                $field = ModuleField::create([
                    'module_id' => $data['module_id'] ?? null,
                    'branch_id' => $data['branch_id'] ?? null,
                    'entity' => $data['entity'],
                    'key' => $data['key'],
                    'label' => $data['label'],
                    'field_type' => $data['field_type'] ?? 'text',
                    'placeholder' => $data['placeholder'] ?? null,
                    'help_text' => $data['help_text'] ?? null,
                    'default_value' => $data['default_value'] ?? null,
                    'options' => $data['options'] ?? null,
                    'validation_rules' => $data['validation_rules'] ?? null,
                    'is_required' => $data['is_required'] ?? false,
                    'is_visible' => $data['is_visible'] ?? true,
                    'is_editable' => $data['is_editable'] ?? true,
                    'sort_order' => $data['sort_order'] ?? 0,
                ]);

                $this->clearCache($data['entity']);
                
                return $field;
            },
            operation: 'createField',
            context: $data
        );
    }

    /**
     * Update a dynamic field
     */
    public function updateField(ModuleField $field, array $data): ModuleField
    {
        return $this->handleServiceOperation(
            callback: function () use ($field, $data) {
                $field->update($data);
                $this->clearCache($field->entity);
                return $field->fresh();
            },
            operation: 'updateField',
            context: ['field_id' => $field->id, ...$data]
        );
    }

    /**
     * Delete a dynamic field
     */
    public function deleteField(ModuleField $field): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($field) {
                $entity = $field->entity;
                $result = $field->delete();
                $this->clearCache($entity);
                return $result;
            },
            operation: 'deleteField',
            context: ['field_id' => $field->id],
            defaultValue: false
        );
    }

    /**
     * Reorder fields
     */
    public function reorderFields(array $fieldIds): void
    {
        $this->handleServiceOperation(
            callback: function () use ($fieldIds) {
                $entity = null;
                
                foreach ($fieldIds as $order => $id) {
                    $field = ModuleField::find($id);
                    if ($field) {
                        $field->update(['sort_order' => $order]);
                        $entity = $entity ?? $field->entity;
                    }
                }

                if ($entity) {
                    $this->clearCache($entity);
                }
            },
            operation: 'reorderFields',
            context: ['field_ids' => $fieldIds]
        );
    }

    /**
     * Clear field cache
     */
    protected function clearCache(string $entity): void
    {
        // Clear all cache keys related to this entity
        Cache::forget("dynamic_fields:{$entity}:*");
    }

    /**
     * Get available field types
     */
    public function getAvailableFieldTypes(): array
    {
        return collect(self::FIELD_TYPES)->map(fn($config, $key) => [
            'value' => $key,
            'label' => $config['label'],
        ])->values()->toArray();
    }
}
