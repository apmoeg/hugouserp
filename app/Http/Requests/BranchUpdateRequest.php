<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Traits\HasMultilingualValidation;
use Illuminate\Foundation\Http\FormRequest;

class BranchUpdateRequest extends FormRequest
{
    use HasMultilingualValidation;

    public function authorize(): bool
    {
        return $this->user()?->can('branches.update') ?? false;
    }

    public function rules(): array
    {
        $branch = $this->route('branch'); // Model binding

        return [
            'name' => array_merge(['sometimes'], $this->multilingualString(required: true, max: 255), ['unique:branches,name,'.$branch?->id]),
            'code' => ['sometimes', 'string', 'max:50', 'unique:branches,code,'.$branch?->id],
            'address' => $this->unicodeText(required: false, max: 500),
            'is_active' => ['boolean'],
        ];
    }
}
