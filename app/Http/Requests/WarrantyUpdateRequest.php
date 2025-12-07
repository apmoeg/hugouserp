<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WarrantyUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('motorcycle.warranties.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'provider' => ['sometimes', 'string', 'max:190'],
            'start_at' => ['sometimes', 'date'],
            'end_at' => ['sometimes', 'date', 'after:start_at'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
