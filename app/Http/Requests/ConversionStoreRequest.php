<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConversionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('wood.conversions.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'input_uom' => ['required', 'string', 'max:20'],
            'input_qty' => ['required', 'numeric', 'gt:0'],
            'output_uom' => ['required', 'string', 'max:20'],
            'output_qty' => ['required', 'numeric', 'gte:0'],
        ];
    }
}
