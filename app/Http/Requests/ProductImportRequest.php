<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('products.import') ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file'],
        ];
    }
}
