<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseCancelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.cancel') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
