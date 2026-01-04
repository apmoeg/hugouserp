<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\ModuleFieldServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\Cache;

/**
 * DynamicFormService - Extended form field utilities
 * 
 * NOTE: This service EXTENDS the existing ModuleFieldService functionality.
 * Use ModuleFieldService for core field operations (formSchema, exportColumns).
 * Use this service for:
 *   - Field type configurations and UI components
 *   - Validation rule building
 *   - Cached field retrieval for performance
 *   - Branch manager helper methods
 * 
 * This is a COMPLEMENTARY service, not a replacement.
 */
class DynamicFormService
{
    use HandlesServiceErrors;

    protected const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        protected ModuleFieldServiceInterface $moduleFieldService
    ) {}

    /**
     * Available field types with their UI configurations
     * This adds UI component info to the existing field system
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
     * Get form schema with caching (delegates to ModuleFieldService)
     */
    public function getFormSchema(string $moduleKey, string $entity, ?int $branchId = null): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($moduleKey, $entity, $branchId) {
                $cacheKey = "form_schema:{$moduleKey}:{$entity}:{$branchId}";
                
                return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($moduleKey, $entity, $branchId) {
                    return $this->moduleFieldService->formSchema($moduleKey, $entity, $branchId);
                });
            },
            operation: 'getFormSchema',
            context: ['module' => $moduleKey, 'entity' => $entity, 'branch_id' => $branchId],
            defaultValue: []
        );
    }

    /**
     * Get form schema with UI component info added
     */
    public function getFormSchemaWithComponents(string $moduleKey, string $entity, ?int $branchId = null): array
    {
        $schema = $this->getFormSchema($moduleKey, $entity, $branchId);
        
        return array_map(function ($field) {
            $typeConfig = self::FIELD_TYPES[$field['type']] ?? self::FIELD_TYPES['text'];
            return array_merge($field, [
                'component' => $typeConfig['component'],
                'input_type' => $typeConfig['input_type'] ?? null,
                'attributes' => $typeConfig['attributes'] ?? [],
            ]);
        }, $schema);
    }

    /**
     * Build validation rules from form schema
     */
    public function getValidationRules(string $moduleKey, string $entity, ?int $branchId = null): array
    {
        $schema = $this->getFormSchema($moduleKey, $entity, $branchId);
        $rules = [];

        foreach ($schema as $field) {
            $fieldRules = $field['rules'] ?? [];

            // Add required rule if specified
            if (!empty($field['required'])) {
                array_unshift($fieldRules, 'required');
            } else {
                array_unshift($fieldRules, 'nullable');
            }

            // Add type-specific rules
            $typeRules = $this->getTypeValidationRules($field['type'] ?? 'text');
            $fieldRules = array_merge($fieldRules, $typeRules);

            $rules["extra.{$field['name']}"] = $fieldRules;
        }

        return $rules;
    }

    /**
     * Get type-specific validation rules
     */
    protected function getTypeValidationRules(string $type): array
    {
        return match($type) {
            'email' => ['email'],
            'url' => ['url'],
            'number', 'money' => ['numeric'],
            'percentage' => ['numeric', 'min:0', 'max:100'],
            'date', 'datetime' => ['date'],
            'checkbox' => ['boolean'],
            'file', 'image' => ['file'],
            default => [],
        };
    }

    /**
     * Clear form schema cache
     */
    public function clearCache(string $moduleKey, string $entity): void
    {
        Cache::forget("form_schema:{$moduleKey}:{$entity}:*");
    }

    /**
     * Get available field types for UI
     */
    public function getAvailableFieldTypes(): array
    {
        return collect(self::FIELD_TYPES)->map(fn($config, $key) => [
            'value' => $key,
            'label' => $config['label'],
        ])->values()->toArray();
    }

    /**
     * Get field type configuration
     */
    public function getFieldTypeConfig(string $type): array
    {
        return self::FIELD_TYPES[$type] ?? self::FIELD_TYPES['text'];
    }
}
