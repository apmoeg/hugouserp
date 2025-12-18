<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('suppliers.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:190', 'unique:suppliers,email'],
            'address' => ['nullable', 'string', 'max:500'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            // Financial fields
            'payment_terms' => ['nullable', 'string', 'in:immediate,net15,net30,net60,net90'],
            'payment_due_days' => ['nullable', 'integer', 'min:0'],
            'minimum_order_value' => ['nullable', 'numeric', 'min:0'],
            // Rating fields
            'supplier_rating' => ['nullable', 'string', 'max:191'],
            'quality_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'delivery_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'service_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
        ];
    }
}
