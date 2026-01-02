<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Traits\HasMultilingualValidation;
use Illuminate\Foundation\Http\FormRequest;

class PropertyUpdateRequest extends FormRequest
{
    use HasMultilingualValidation;

    public function authorize(): bool
    {
        return $this->user()?->can('rental.properties.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => $this->multilingualString(required: false, max: 255), // 'sometimes' handled automatically
            'address' => array_merge(['sometimes', 'nullable'], $this->unicodeText(required: false, max: 500)),
            'notes' => array_merge(['sometimes', 'nullable'], $this->unicodeText(required: false)),
        ];
    }
}
