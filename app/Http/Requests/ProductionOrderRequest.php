<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Traits\HasMultilingualValidation;
use Illuminate\Foundation\Http\FormRequest;

class ProductionOrderRequest extends FormRequest
{
    use HasMultilingualValidation;

    public function authorize(): bool
    {
        return $this->user()->can('manufacturing.create') || $this->user()->can('manufacturing.update');
    }

    public function rules(): array
    {
        return [
            'bom_id' => ['required', 'exists:bills_of_materials,id'],
            'order_number' => ['sometimes', 'string', 'max:50', 'unique:production_orders,order_number,'.($this->route('order') ? $this->route('order')->id : 'NULL')],
            'quantity_planned' => ['required', 'numeric', 'min:0.01'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'status' => ['sometimes', 'in:draft,planned,in_progress,completed,cancelled'],
            'work_center_id' => ['nullable', 'exists:work_centers,id'],
            'notes' => $this->unicodeText(required: false),
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

        if ($this->isMethod('POST') && ! $this->has('status')) {
            $this->merge([
                'status' => 'draft',
            ]);
        }
    }
}
