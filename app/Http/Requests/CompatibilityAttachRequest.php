<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompatibilityAttachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('spares.compatibility.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'compatible_with_id' => ['required', 'integer', 'exists:products,id'],
        ];
    }
}
