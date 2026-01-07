<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FixedAssetUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('fixed-assets.edit');
    }

    public function rules(): array
    {
        $assetId = $this->route('asset') ? $this->route('asset')->id : 'NULL';

        return [
            'asset_code' => ['sometimes', 'required', 'string', 'max:50', 'unique:fixed_assets,asset_code,'.$assetId],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['sometimes', 'required', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['sometimes', 'required', 'date'],
            'purchase_cost' => ['sometimes', 'required', 'numeric', 'min:0'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life_years' => ['nullable', 'integer', 'min:0'],
            'useful_life_months' => ['nullable', 'integer', 'min:0', 'max:11'],
            'depreciation_method' => ['sometimes', 'required', 'in:straight_line,declining_balance,units_of_production'],
            'depreciation_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'depreciation_start_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'required', 'in:active,inactive,disposed,under_maintenance'],
            'disposal_date' => ['nullable', 'date'],
            'disposal_amount' => ['nullable', 'numeric', 'min:0'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'warranty_expiry_date' => ['nullable', 'date'],
        ];
    }
}
