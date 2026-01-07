<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Traits\HasMultilingualValidation;
use Illuminate\Foundation\Http\FormRequest;

class ProjectTimeLogRequest extends FormRequest
{
    use HasMultilingualValidation;

    public function authorize(): bool
    {
        return $this->user()->can('projects.timelogs.manage');
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'exists:projects,id'],
            'task_id' => ['nullable', 'exists:project_tasks,id'],
            'user_id' => ['required', 'exists:users,id'],
            'log_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'hours' => ['nullable', 'numeric', 'min:0'],
            'description' => $this->unicodeText(required: true),
            'is_billable' => ['boolean'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Auto-calculate hours if not provided
        if (! $this->has('hours') && $this->has('start_time') && $this->has('end_time')) {
            $start = \Carbon\Carbon::createFromFormat('H:i', $this->start_time);
            $end = \Carbon\Carbon::createFromFormat('H:i', $this->end_time);
            $hours = $end->diffInMinutes($start) / 60;

            $this->merge([
                'hours' => round($hours, 2),
            ]);
        }

        if (! $this->has('is_billable')) {
            $this->merge([
                'is_billable' => true,
            ]);
        }

        if (! $this->has('user_id')) {
            $this->merge([
                'user_id' => $this->user()->id,
            ]);
        }
    }
}
