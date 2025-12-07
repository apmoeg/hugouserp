<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TenantArchiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('rental.tenants.archive') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
