<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Traits\HasMultilingualValidation;
use Illuminate\Foundation\Http\FormRequest;

class BranchStoreRequest extends FormRequest
{
    use HasMultilingualValidation;

    public function authorize(): bool
    {
        return $this->user()?->can('branches.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => array_merge($this->multilingualString(required: true, max: 255), ['unique:branches,name']),
            'code' => ['nullable', 'string', 'max:50', 'unique:branches,code'],
            'address' => $this->unicodeText(required: false, max: 500),
            'is_active' => ['boolean'],
        ];
    }
}
