<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaleReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sales.return') ?? false;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
