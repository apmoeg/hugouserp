<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('hrm.employees.create') || $this->user()->can('hr.manage-employees');
    }

    public function rules(): array
    {
        return [
            'employee_code' => ['required', 'string', 'max:50', 'unique:hr_employees,employee_code'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:hr_employees,email'],
            'phone' => ['required', 'string', 'max:20'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['required', 'in:male,female'],
            'address' => ['nullable', 'string'],
            'hire_date' => ['required', 'date'],
            'position' => ['required', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'salary' => ['required', 'numeric', 'min:0'],
            'salary_type' => ['required', 'in:monthly,daily,hourly'],
            'employment_type' => ['required', 'in:full_time,part_time,contract,temporary'],
            'status' => ['required', 'in:active,inactive,terminated,on_leave'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:100'],
            // Contract fields
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after_or_equal:contract_start_date', 'required_with:contract_start_date'],
            // Work permit fields
            'work_permit_number' => ['nullable', 'string', 'max:100'],
            'work_permit_expiry' => ['nullable', 'date'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('branch_id') && $this->user()->branch_id) {
            $this->merge([
                'branch_id' => $this->user()->branch_id,
            ]);
        }

        if (!$this->has('status')) {
            $this->merge([
                'status' => 'active',
            ]);
        }
    }
}
