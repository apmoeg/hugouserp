<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('suppliers.update') ?? false;
    }

    public function rules(): array
    {
        $supplier = $this->route('supplier'); // Model binding

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'nullable', 'email', 'max:190', 'unique:suppliers,email,'.$supplier?->id],
        ];
    }
}
