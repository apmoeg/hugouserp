<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('customers.update') ?? false;
    }

    public function rules(): array
    {
        $customer = $this->route('customer'); // Model binding

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'nullable', 'email', 'max:190', 'unique:customers,email,'.$customer?->id],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'city' => ['sometimes', 'nullable', 'string', 'max:191'],
            'country' => ['sometimes', 'nullable', 'string', 'max:191'],
            // Financial fields
            'credit_limit' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'payment_terms' => ['sometimes', 'nullable', 'string', 'in:immediate,net15,net30,net60,net90'],
            'payment_terms_days' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'payment_due_days' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'customer_group' => ['sometimes', 'nullable', 'string', 'max:191'],
            'preferred_payment_method' => ['sometimes', 'nullable', 'string', 'max:191'],
        ];
    }
}
