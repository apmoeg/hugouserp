<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnitStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('rental.units.status') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string'],
        ];
    }
}
