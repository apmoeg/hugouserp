<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnitUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('rental.units.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:100'],
            'status' => ['sometimes', 'string'],
            'area' => ['sometimes', 'numeric', 'gte:0'],
        ];
    }
}
