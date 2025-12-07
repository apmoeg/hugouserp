<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnitStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('rental.units.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'property_id' => ['required', 'exists:properties,id'],
            'code' => ['required', 'string', 'max:100'],
            'status' => ['sometimes', 'string'],
        ];
    }
}
