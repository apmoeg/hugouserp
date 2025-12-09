<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateStockRequest extends FormRequest
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
            'updates' => 'required|array|min:1',
            'updates.*.product_id' => 'required_without:updates.*.external_id|exists:products,id',
            'updates.*.external_id' => 'required_without:updates.*.product_id|string',
            'updates.*.qty' => 'required|numeric',
            'updates.*.direction' => 'required|in:in,out,set',
            'updates.*.reason' => 'nullable|string|max:255',
            'updates.*.warehouse_id' => 'nullable|exists:warehouses,id',
        ];
    }
}
