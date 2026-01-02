<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Traits\HasMultilingualValidation;
use Illuminate\Foundation\Http\FormRequest;

class WasteStoreRequest extends FormRequest
{
    use HasMultilingualValidation;

    public function authorize(): bool
    {
        return $this->user()?->can('wood.waste.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => $this->multilingualString(required: false, max: 50),
            'qty' => ['required', 'numeric', 'gte:0'],
            'uom' => ['sometimes', 'string', 'max:10'],
            'notes' => $this->unicodeText(required: false, max: 255),
        ];
    }
}
