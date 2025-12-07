<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoicePenaltyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('rental.invoices.penalty') ?? false;
    }

    public function rules(): array
    {
        return [
            'penalty' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
