<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContractUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('rental.contracts.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after:start_date'],
            'rent' => ['sometimes', 'numeric', 'gt:0'],
            'status' => ['sometimes', 'string'],
        ];
    }
}
