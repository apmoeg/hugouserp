<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BillOfMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manufacturing.create') || $this->user()->can('manufacturing.update');
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'status' => ['sometimes', 'in:draft,active,archived'],
            'scrap_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_multi_level' => ['boolean'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.scrap_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => __('Product is required'),
            'product_id.exists' => __('Selected product does not exist'),
            'name.required' => __('BOM name is required'),
            'quantity.required' => __('Output quantity is required'),
            'items.required' => __('At least one material item is required'),
            'items.min' => __('At least one material item is required'),
        ];
    }

    public function attributes(): array
    {
        return [
            'product_id' => __('Product'),
            'name' => __('BOM Name'),
            'name_ar' => __('BOM Name (Arabic)'),
            'description' => __('Description'),
            'quantity' => __('Output Quantity'),
            'status' => __('Status'),
            'scrap_percentage' => __('Scrap Percentage'),
            'branch_id' => __('Branch'),
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('branch_id') && $this->user()->branch_id) {
            $this->merge([
                'branch_id' => $this->user()->branch_id,
            ]);
        }

        if ($this->isMethod('POST') && !$this->has('status')) {
            $this->merge([
                'status' => 'draft',
            ]);
        }
    }
}
