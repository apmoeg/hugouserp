<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('customers.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:190', 'unique:customers,email'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:191'],
            'country' => ['nullable', 'string', 'max:191'],
            // Financial fields
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_terms' => ['nullable', 'string', 'in:immediate,net15,net30,net60,net90'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0'],
            'payment_due_days' => ['nullable', 'integer', 'min:0'],
            'customer_group' => ['nullable', 'string', 'max:191'],
            'preferred_payment_method' => ['nullable', 'string', 'max:191'],
        ];
    }
}
