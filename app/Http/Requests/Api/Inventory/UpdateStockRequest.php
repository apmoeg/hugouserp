<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => 'required_without:external_id|exists:products,id',
            'external_id' => 'required_without:product_id|string',
            'qty' => 'required|numeric',
            'direction' => 'required|in:in,out,set',
            'reason' => 'nullable|string|max:255',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ];
    }
}
