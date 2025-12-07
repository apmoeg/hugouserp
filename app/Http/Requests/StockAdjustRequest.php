<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockAdjustRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('stock.adjust') ?? false;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'qty' => ['required', 'numeric', 'not_in:0'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
