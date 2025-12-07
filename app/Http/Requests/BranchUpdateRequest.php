<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BranchUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('branches.update') ?? false;
    }

    public function rules(): array
    {
        $branch = $this->route('branch'); // Model binding

        return [
            'name' => ['sometimes', 'string', 'max:255', 'unique:branches,name,'.$branch?->id],
            'code' => ['sometimes', 'string', 'max:50', 'unique:branches,code,'.$branch?->id],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }
}
