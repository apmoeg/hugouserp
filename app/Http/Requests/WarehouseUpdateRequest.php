<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WarehouseUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('warehouses.update') ?? false;
    }

    public function rules(): array
    {
        $warehouse = $this->route('warehouse'); // Model binding

        return [
            'name' => ['sometimes', 'string', 'max:255', 'unique:warehouses,name,'.$warehouse?->id],
            'code' => ['sometimes', 'string', 'max:50', 'unique:warehouses,code,'.$warehouse?->id],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
