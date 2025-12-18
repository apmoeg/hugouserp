<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Traits\HasPaymentTermsValidation;
use Illuminate\Foundation\Http\FormRequest;

class CustomerUpdateRequest extends FormRequest
{
    use HasPaymentTermsValidation;

    public function authorize(): bool
    {
        return $this->user()?->can('customers.update') ?? false;
    }

    public function rules(): array
    {
        $customer = $this->route('customer'); // Model binding

        return array_merge([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'nullable', 'email', 'max:190', 'unique:customers,email,'.$customer?->id],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'city' => ['sometimes', 'nullable', 'string', 'max:191'],
            'country' => ['sometimes', 'nullable', 'string', 'max:191'],
            // Financial fields
            'credit_limit' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'customer_group' => ['sometimes', 'nullable', 'string', 'max:191'],
            'preferred_payment_method' => ['sometimes', 'nullable', 'string', 'max:191'],
        ], 
        $this->paymentTermsRules(false),
        $this->paymentTermsDaysRules(false),
        $this->paymentDueDaysRules(false)
        );
    }
}
