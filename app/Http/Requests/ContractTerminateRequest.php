<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContractTerminateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('rental.contracts.terminate') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
