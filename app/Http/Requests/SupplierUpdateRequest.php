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
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'tax_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            // Financial fields
            'payment_terms' => ['sometimes', 'nullable', 'string', 'in:immediate,net15,net30,net60,net90'],
            'payment_due_days' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'minimum_order_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            // Rating fields
            'supplier_rating' => ['sometimes', 'nullable', 'string', 'max:191'],
            'quality_rating' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:5'],
            'delivery_rating' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:5'],
            'service_rating' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:5'],
        ];
    }
}
