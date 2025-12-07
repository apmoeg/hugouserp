<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceCollectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('rental.invoices.collect') ?? false;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
