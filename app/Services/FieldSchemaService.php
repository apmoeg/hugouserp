<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\FieldSchemaServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Arr;
use Illuminate\Validation\Factory as ValidatorFactory;

class FieldSchemaService implements FieldSchemaServiceInterface
{
    use HandlesServiceErrors;

    public function __construct(protected ValidatorFactory $validator) {}

    public function for(string $module, ?int $branchId = null): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($module, $branchId) {
                if (class_exists(\App\Models\FieldSchema::class)) {
                    $q = \App\Models\FieldSchema::query()->where('module_key', $module);
                    if ($branchId) {
                        $q->where('branch_id', $branchId);
                    }

                    return $q->orderBy('position')->get(['name', 'type', 'rules', 'options'])->map(function ($row) {
                        return [
                            'name' => (string) $row->name,
                            'type' => (string) $row->type,
                            'rules' => (array) ($row->rules ?? []),
                            'options' => (array) ($row->options ?? []),
                        ];
                    })->all();
                }

                return (array) config('modules.'.$module.'.fields', []);
            },
            operation: 'for',
            context: ['module' => $module, 'branch_id' => $branchId],
            defaultValue: []
        );
    }

    public function validate(string $module, array $payload, ?int $branchId = null): Validator
    {
        return $this->handleServiceOperation(
            callback: function () use ($module, $payload, $branchId) {
                $schema = $this->for($module, $branchId);
                $rules = [];
                foreach ($schema as $f) {
                    $rules[$f['name']] = $f['rules'] ?? [];
                }

                return $this->validator->make($payload, $rules);
            },
            operation: 'validate',
            context: ['module' => $module, 'branch_id' => $branchId]
        );
    }

    public function filter(string $module, array $payload, ?int $branchId = null): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($module, $payload, $branchId) {
                $schema = $this->for($module, $branchId);
                $names = array_column($schema, 'name');

                return Arr::only($payload, $names);
            },
            operation: 'filter',
            context: ['module' => $module, 'branch_id' => $branchId],
            defaultValue: []
        );
    }
}
