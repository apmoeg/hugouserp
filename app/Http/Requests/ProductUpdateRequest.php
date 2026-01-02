<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Traits\HasMultilingualValidation;
use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
{
    use HasMultilingualValidation;

    public function authorize(): bool
    {
        return $this->user()?->can('products.update') ?? false;
    }

    public function rules(): array
    {
        $product = $this->route('product'); // Model binding

        return [
            'name' => $this->multilingualString(required: false, max: 255), // 'sometimes' handled automatically
            'sku' => ['sometimes', 'string', 'max:100', 'unique:products,sku,'.$product?->id],
            'barcode' => ['sometimes', 'string', 'max:100', 'unique:products,barcode,'.$product?->id],
            'default_price' => ['sometimes', 'numeric', 'min:0'],
            'cost' => ['sometimes', 'numeric', 'min:0'],
            'description' => $this->unicodeText(required: false),
            'category_id' => ['nullable', 'exists:product_categories,id'],
            'tax_id' => ['nullable', 'exists:taxes,id'],
            // Inventory tracking fields
            'min_stock' => ['sometimes', 'numeric', 'min:0'],
            'max_stock' => ['sometimes', 'numeric', 'min:0'],
            'reorder_point' => ['sometimes', 'numeric', 'min:0'],
            'lead_time_days' => ['sometimes', 'numeric', 'min:0', 'max:9999.9'],
            'location_code' => $this->flexibleCode(required: false, max: 191),
        ];
    }
}
