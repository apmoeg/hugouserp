<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.return') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
