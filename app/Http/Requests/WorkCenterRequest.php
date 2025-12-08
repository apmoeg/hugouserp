<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manufacturing.create') || $this->user()->can('manufacturing.update');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:work_centers,code,' . ($this->route('workCenter') ? $this->route('workCenter')->id : 'NULL')],
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'capacity_per_hour' => ['required', 'numeric', 'min:0.01'],
            'cost_per_hour' => ['nullable', 'numeric', 'min:0'],
            'efficiency_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => __('Work center code is required'),
            'code.unique' => __('This work center code already exists'),
            'name.required' => __('Work center name is required'),
            'capacity_per_hour.required' => __('Capacity per hour is required'),
            'capacity_per_hour.min' => __('Capacity must be greater than zero'),
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => __('Code'),
            'name' => __('Name'),
            'name_ar' => __('Name (Arabic)'),
            'description' => __('Description'),
            'capacity_per_hour' => __('Capacity per Hour'),
            'cost_per_hour' => __('Cost per Hour'),
            'efficiency_percentage' => __('Efficiency Percentage'),
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

        if ($this->isMethod('POST') && !$this->has('is_active')) {
            $this->merge([
                'is_active' => true,
            ]);
        }
    }
}
