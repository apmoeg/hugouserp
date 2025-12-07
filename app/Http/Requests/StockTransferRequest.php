<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('stock.transfer') ?? false;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'from_warehouse' => ['required', 'integer', 'exists:warehouses,id'],
            'to_warehouse' => ['required', 'integer', 'different:from_warehouse', 'exists:warehouses,id'],
        ];
    }
}
