<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WarehouseStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('warehouses.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:warehouses,name'],
            'code' => ['nullable', 'string', 'max:50', 'unique:warehouses,code'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
