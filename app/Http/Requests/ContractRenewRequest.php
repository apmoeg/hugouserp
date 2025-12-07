<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContractRenewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('rental.contracts.renew') ?? false;
    }

    public function rules(): array
    {
        return [
            'end_date' => ['required', 'date'],
            'rent' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
