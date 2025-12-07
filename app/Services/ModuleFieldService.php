<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ModuleField;
use App\Services\Contracts\ModuleFieldServiceInterface;
use App\Traits\HandlesServiceErrors;

class ModuleFieldService implements ModuleFieldServiceInterface
{
    use HandlesServiceErrors;

    public function formSchema(string $moduleKey, string $entity, ?int $branchId = null): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($moduleKey, $entity, $branchId) {
                $fields = $this->queryFields($moduleKey, $entity, $branchId)
                    ->visible()
                    ->get();

                if ($fields->isEmpty()) {
                    $configFields = config("modules.{$moduleKey}.entities.{$entity}.fields", []);

                    return array_values(array_map(
                        fn (array $field) => $this->normalizeConfigField($field),
                        $configFields
                    ));
                }

                return $fields->map(fn (ModuleField $field) => $this->normalizeModelField($field))->values()->all();
            },
            operation: 'formSchema',
            context: ['module_key' => $moduleKey, 'entity' => $entity, 'branch_id' => $branchId],
            defaultValue: []
        );
    }

    public function exportColumns(string $moduleKey, string $entity, ?int $branchId = null): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($moduleKey, $entity, $branchId) {
                $fields = $this->queryFields($moduleKey, $entity, $branchId)
                    ->where('show_in_export', true)
                    ->visible()
                    ->get();

                if ($fields->isEmpty()) {
                    $configFields = config("modules.{$moduleKey}.entities.{$entity}.fields", []);

                    return collect($configFields)
                        ->filter(fn ($field) => ($field['show_in_export'] ?? true) === true)
                        ->sortBy('order')
                        ->pluck('name')
                        ->values()
                        ->all();
                }

                return $fields->pluck('name')->values()->all();
            },
            operation: 'exportColumns',
            context: ['module_key' => $moduleKey, 'entity' => $entity, 'branch_id' => $branchId],
            defaultValue: []
        );
    }

    protected function queryFields(string $moduleKey, string $entity, ?int $branchId = null)
    {
        return ModuleField::query()
            ->forModule($moduleKey)
            ->forEntity($entity)
            ->when($branchId !== null, function ($q) use ($branchId) {
                $q->where(function ($inner) use ($branchId) {
                    $inner->whereNull('branch_id')
                        ->orWhere('branch_id', $branchId);
                });
            });
    }

    protected function normalizeModelField(ModuleField $field): array
    {
        return [
            'name' => $field->name,
            'label' => $field->label ?: $field->name,
            'type' => $field->type ?: 'text',
            'options' => $field->options ?? [],
            'rules' => $field->rules ?? [],
            'required' => (bool) $field->is_required,
            'visible' => (bool) $field->is_visible,
            'show_in_list' => (bool) $field->show_in_list,
            'show_in_export' => (bool) $field->show_in_export,
            'order' => $field->order ?? 0,
            'default' => $field->default ?? null,
            'meta' => $field->meta ?? [],
        ];
    }

    protected function normalizeConfigField(array $field): array
    {
        return [
            'name' => $field['name'] ?? '',
            'label' => $field['label'] ?? ($field['name'] ?? ''),
            'type' => $field['type'] ?? 'text',
            'options' => $field['options'] ?? [],
            'rules' => $field['rules'] ?? [],
            'required' => (bool) ($field['required'] ?? false),
            'visible' => (bool) ($field['visible'] ?? true),
            'show_in_list' => (bool) ($field['show_in_list'] ?? false),
            'show_in_export' => (bool) ($field['show_in_export'] ?? true),
            'order' => (int) ($field['order'] ?? 0),
            'default' => $field['default'] ?? null,
            'meta' => $field['meta'] ?? [],
        ];
    }
}
