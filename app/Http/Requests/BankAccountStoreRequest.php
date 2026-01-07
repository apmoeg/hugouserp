<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BankAccountStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('banking.create');
    }

    public function rules(): array
    {
        return [
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:50', 'unique:bank_accounts,account_number'],
            'bank_name' => ['required', 'string', 'max:255'],
            'branch_name' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:3'],
            'account_type' => ['required', 'in:checking,savings,business,other'],
            'initial_balance' => ['required', 'numeric'],
            'current_balance' => ['nullable', 'numeric'],
            'iban' => ['nullable', 'string', 'max:50'],
            'swift_code' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
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

        if (! $this->has('is_active')) {
            $this->merge([
                'is_active' => true,
            ]);
        }

        if (! $this->has('current_balance')) {
            $this->merge([
                'current_balance' => $this->initial_balance,
            ]);
        }
    }
}
