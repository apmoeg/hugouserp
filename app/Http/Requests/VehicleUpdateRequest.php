<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehicleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('motorcycle.vehicles.update') ?? false;
    }

    public function rules(): array
    {
        $vehicle = $this->route('vehicle'); // Model binding

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'vin' => ['sometimes', 'string', 'max:190', 'unique:vehicles,vin,'.$vehicle?->id],
            'price' => ['sometimes', 'numeric', 'gte:0'],
            'cost' => ['sometimes', 'numeric', 'gte:0'],
        ];
    }
}
