<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchasePayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.pay') ?? false;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
