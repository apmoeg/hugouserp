<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehicleStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('motorcycle.vehicles.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'vin' => ['nullable', 'string', 'max:190', 'unique:vehicles,vin'],
            'price' => ['required', 'numeric', 'gte:0'],
            'cost' => ['nullable', 'numeric', 'gte:0'],
        ];
    }
}
