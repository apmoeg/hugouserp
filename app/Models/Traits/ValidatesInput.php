<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Trait for model-level validation and dynamic field handling
 * Makes data entry easier and more controlled for branch managers
 */
trait ValidatesInput
{
    /**
     * Get validation rules for this model
     * Override in child classes to customize
     */
    public function getValidationRules(): array
    {
        return $this->validationRules ?? [];
    }

    /**
     * Get custom validation messages
     * Override in child classes to customize
     */
    public function getValidationMessages(): array
    {
        return $this->validationMessages ?? [];
    }

    /**
     * Get validation attribute names for better error messages
     */
    public function getValidationAttributes(): array
    {
        return $this->validationAttributes ?? [];
    }

    /**
     * Validate the given data against model rules
     */
    public function validateData(array $data): array
    {
        $validator = Validator::make(
            $data,
            $this->getValidationRules(),
            $this->getValidationMessages(),
            $this->getValidationAttributes()
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Get fillable fields with their types for form generation
     */
    public function getFormFields(): array
    {
        $fields = [];

        foreach ($this->fillable as $field) {
            $fields[$field] = [
                'name' => $field,
                'label' => $this->getFieldLabel($field),
                'type' => $this->getFieldType($field),
                'required' => $this->isFieldRequired($field),
                'options' => $this->getFieldOptions($field),
            ];
        }

        return $fields;
    }

    /**
     * Get human-readable label for a field
     */
    protected function getFieldLabel(string $field): string
    {
        // Check for custom labels
        if (isset($this->fieldLabels[$field])) {
            return __($this->fieldLabels[$field]);
        }

        // Generate from field name
        return __(str_replace('_', ' ', ucfirst($field)));
    }

    /**
     * Get field type based on casts and naming conventions
     */
    protected function getFieldType(string $field): string
    {
        // Check casts first
        $casts = $this->casts ?? [];
        if (isset($casts[$field])) {
            $cast = $casts[$field];

            if (str_contains($cast, 'decimal') || str_contains($cast, 'float')) {
                return 'number';
            }
            if ($cast === 'boolean') {
                return 'checkbox';
            }
            if ($cast === 'date') {
                return 'date';
            }
            if ($cast === 'datetime') {
                return 'datetime';
            }
            if ($cast === 'array' || $cast === 'json') {
                return 'json';
            }
            if ($cast === 'integer') {
                return 'number';
            }
        }

        // Infer from field name
        if (str_contains($field, 'email')) {
            return 'email';
        }
        if (str_contains($field, 'phone') || str_contains($field, 'mobile')) {
            return 'tel';
        }
        if (str_contains($field, 'password')) {
            return 'password';
        }
        if (str_contains($field, 'url') || str_contains($field, 'website')) {
            return 'url';
        }
        if (str_contains($field, 'date') || str_ends_with($field, '_at')) {
            return 'date';
        }
        if (str_contains($field, 'notes') || str_contains($field, 'description') || str_contains($field, 'address')) {
            return 'textarea';
        }
        if (str_ends_with($field, '_id')) {
            return 'select';
        }
        if (str_contains($field, 'image') || str_contains($field, 'thumbnail') || str_contains($field, 'photo')) {
            return 'file';
        }

        return 'text';
    }

    /**
     * Check if a field is required based on validation rules
     */
    protected function isFieldRequired(string $field): bool
    {
        $rules = $this->getValidationRules();

        if (! isset($rules[$field])) {
            return false;
        }

        $rule = $rules[$field];
        if (is_string($rule)) {
            return str_contains($rule, 'required');
        }
        if (is_array($rule)) {
            return in_array('required', $rule);
        }

        return false;
    }

    /**
     * Get options for select fields
     * Override in child classes for custom options
     */
    protected function getFieldOptions(string $field): ?array
    {
        // Common status options
        if ($field === 'status') {
            return $this->getStatusOptions();
        }

        return null;
    }

    /**
     * Get status options for the model
     * Override in child classes for custom statuses
     */
    public function getStatusOptions(): array
    {
        return [
            'active' => __('Active'),
            'inactive' => __('Inactive'),
            'pending' => __('Pending'),
        ];
    }

    /**
     * Get related model options for select fields
     */
    public function getRelationOptions(string $relation, string $labelColumn = 'name', string $valueColumn = 'id'): array
    {
        if (! method_exists($this, $relation)) {
            return [];
        }

        $related = $this->{$relation}()->getRelated();

        return $related->query()
            ->when(method_exists($related, 'scopeActive'), fn ($q) => $q->active())
            ->pluck($labelColumn, $valueColumn)
            ->toArray();
    }

    /**
     * Create a record with validation
     */
    public static function createValidated(array $data): static
    {
        $instance = new static;
        $validatedData = $instance->validateData($data);

        return static::create($validatedData);
    }

    /**
     * Update a record with validation
     */
    public function updateValidated(array $data): bool
    {
        $validatedData = $this->validateData($data);

        return $this->update($validatedData);
    }
}
