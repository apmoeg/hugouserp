<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContractStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('rental.contracts.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'unit_id' => ['required', 'exists:rental_units,id'],
            'tenant_id' => ['required', 'exists:tenants,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'rent' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
