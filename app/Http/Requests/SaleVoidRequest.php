<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaleVoidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sales.void') ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
