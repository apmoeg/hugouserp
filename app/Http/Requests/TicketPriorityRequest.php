<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Traits\HasMultilingualValidation;
use Illuminate\Foundation\Http\FormRequest;

class TicketPriorityRequest extends FormRequest
{
    use HasMultilingualValidation;

    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.manage');
    }

    public function rules(): array
    {
        return [
            'name' => $this->multilingualString(required: true, max: 100),
            'name_ar' => $this->multilingualString(required: false, max: 100),
            'level' => ['required', 'integer', 'min:1', 'max:5'],
            'color' => ['nullable', 'string', 'max:20'],
            'response_time_minutes' => ['required', 'integer', 'min:1'],
            'resolution_time_minutes' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Priority name is required'),
            'level.required' => __('Priority level is required'),
            'level.min' => __('Priority level must be between 1 and 5'),
            'level.max' => __('Priority level must be between 1 and 5'),
            'response_time_minutes.required' => __('Response time is required'),
            'response_time_minutes.min' => __('Response time must be at least 1 minute'),
            'resolution_time_minutes.required' => __('Resolution time is required'),
            'resolution_time_minutes.min' => __('Resolution time must be at least 1 minute'),
        ];
    }

    protected function prepareForValidation(): void
    {
        // Set default is_active
        if (! $this->has('is_active') && $this->isMethod('POST')) {
            $this->merge([
                'is_active' => true,
            ]);
        }
    }
}
