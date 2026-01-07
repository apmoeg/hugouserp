<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Traits\HasMultilingualValidation;
use Illuminate\Foundation\Http\FormRequest;

class FixedAssetStoreRequest extends FormRequest
{
    use HasMultilingualValidation;

    public function authorize(): bool
    {
        return $this->user()->can('fixed-assets.create');
    }

    public function rules(): array
    {
        return [
            'asset_code' => $this->flexibleCode(required: true, max: 50), // Allow separators
            'name' => $this->multilingualString(required: true, max: 255),
            'description' => $this->unicodeText(required: false),
            'category' => $this->multilingualString(required: true, max: 100),
            'location' => $this->multilingualString(required: false, max: 255),
            'purchase_date' => ['required', 'date'],
            'purchase_cost' => ['required', 'numeric', 'min:0'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life_years' => ['nullable', 'integer', 'min:0'],
            'useful_life_months' => ['nullable', 'integer', 'min:0', 'max:11'],
            'depreciation_method' => ['required', 'in:straight_line,declining_balance,units_of_production'],
            'depreciation_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'depreciation_start_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive,disposed,under_maintenance'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'serial_number' => $this->flexibleCode(required: false, max: 100),
            'model' => $this->multilingualString(required: false, max: 100),
            'manufacturer' => $this->multilingualString(required: false, max: 100),
            'warranty_expiry_date' => ['nullable', 'date'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('branch_id') && $this->user()->branch_id) {
            $this->merge([
                'branch_id' => $this->user()->branch_id,
            ]);
        }

        if (! $this->has('status')) {
            $this->merge([
                'status' => 'active',
            ]);
        }
    }
}
