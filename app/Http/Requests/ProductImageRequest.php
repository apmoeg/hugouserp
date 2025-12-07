<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('products.image.upload') ?? false;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image'],
        ];
    }
}
