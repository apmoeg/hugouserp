<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WarrantyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('motorcycle.warranties.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'provider' => ['sometimes', 'string', 'max:190'],
            'start_at' => ['sometimes', 'date'],
            'end_at' => ['sometimes', 'date', 'after:start_at'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
