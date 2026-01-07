<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Traits\HasMultilingualValidation;
use Illuminate\Foundation\Http\FormRequest;

class TicketSLAPolicyRequest extends FormRequest
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
            'description' => $this->unicodeText(required: false),
            'response_time_minutes' => ['required', 'integer', 'min:1'],
            'resolution_time_minutes' => ['required', 'integer', 'min:1'],
            'business_hours_only' => ['boolean'],
            'business_hours_start' => ['nullable', 'required_if:business_hours_only,true', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'],
            'business_hours_end' => ['nullable', 'required_if:business_hours_only,true', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'],
            'working_days' => ['nullable', 'array'],
            'working_days.*' => ['integer', 'min:0', 'max:6'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('SLA policy name is required'),
            'response_time_minutes.required' => __('Response time is required'),
            'response_time_minutes.min' => __('Response time must be at least 1 minute'),
            'resolution_time_minutes.required' => __('Resolution time is required'),
            'resolution_time_minutes.min' => __('Resolution time must be at least 1 minute'),
            'business_hours_start.required_if' => __('Business hours start time is required when business hours only is enabled'),
            'business_hours_start.regex' => __('Business hours start time must be in HH:MM format'),
            'business_hours_end.required_if' => __('Business hours end time is required when business hours only is enabled'),
            'business_hours_end.regex' => __('Business hours end time must be in HH:MM format'),
            'working_days.*.min' => __('Invalid working day value'),
            'working_days.*.max' => __('Invalid working day value'),
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

        // Set default working days (Monday to Friday = 1 to 5)
        if (! $this->has('working_days') && $this->business_hours_only) {
            $this->merge([
                'working_days' => [1, 2, 3, 4, 5],
            ]);
        }
    }
}
