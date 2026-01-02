<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Traits\HasMultilingualValidation;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    use HasMultilingualValidation;

    public function authorize(): bool
    {
        return $this->user()->can('hrm.attendance.create') || $this->user()->can('hrm.attendance.view');
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:hr_employees,id'],
            'date' => ['required', 'date'],
            'check_in' => ['required', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i', 'after:check_in'],
            'status' => ['required', 'in:present,absent,late,half_day,on_leave'],
            'notes' => $this->unicodeText(required: false),
            'overtime_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
        ];
    }
}
