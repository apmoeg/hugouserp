<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WasteStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('wood.waste.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', 'max:50'],
            'qty' => ['required', 'numeric', 'gte:0'],
            'uom' => ['sometimes', 'string', 'max:10'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
