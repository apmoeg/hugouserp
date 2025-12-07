<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
