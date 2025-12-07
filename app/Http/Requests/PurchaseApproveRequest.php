<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseApproveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.approve') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
