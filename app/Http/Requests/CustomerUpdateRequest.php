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
        ];
    }
}
