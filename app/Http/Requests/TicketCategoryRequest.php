<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Traits\HasMultilingualValidation;
use Illuminate\Foundation\Http\FormRequest;

class TicketCategoryRequest extends FormRequest
{
    use HasMultilingualValidation;

    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.manage');
    }

    public function rules(): array
    {
        return [
            'name' => $this->multilingualString(required: true, max: 255),
            'name_ar' => $this->arabicName(required: false, max: 255),
            'description' => $this->unicodeText(required: false),
            'parent_id' => ['nullable', 'exists:ticket_categories,id'],
            'default_assignee_id' => ['nullable', 'exists:users,id'],
            'sla_policy_id' => ['nullable', 'exists:ticket_sla_policies,id'],
            'color' => ['nullable', 'string', 'max:20'],
            'icon' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Category name is required'),
            'parent_id.exists' => __('Selected parent category does not exist'),
            'default_assignee_id.exists' => __('Selected default assignee does not exist'),
            'sla_policy_id.exists' => __('Selected SLA policy does not exist'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $field = $this->isMethod('POST') ? 'created_by' : 'updated_by';

        $this->merge([
            $field => $this->user()->id,
        ]);

        // Set default is_active
        if (! $this->has('is_active') && $this->isMethod('POST')) {
            $this->merge([
                'is_active' => true,
            ]);
        }
    }
}
