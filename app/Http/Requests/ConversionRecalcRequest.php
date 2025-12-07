<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConversionRecalcRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('wood.conversions.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
        ];
    }
}
