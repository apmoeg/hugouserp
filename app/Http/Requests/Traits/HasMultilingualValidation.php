<?php

declare(strict_types=1);

namespace App\Http\Requests\Traits;

/**
 * Shared validation rules for multilingual/Unicode fields.
 *
 * This trait provides validation rules that properly support Arabic, Unicode, and other non-Latin scripts.
 * Use these instead of 'alpha' or 'ascii' validations which block non-English text.
 *
 * @example
 * ```php
 * class MyFormRequest extends FormRequest
 * {
 *     use HasMultilingualValidation;
 *
 *     public function rules(): array
 *     {
 *         return [
 *             'name' => $this->multilingualString(required: true, max: 255),
 *             'description' => $this->multilingualText(required: false, max: 2000),
 *             'code' => $this->alphanumericCode(required: true, length: 10),
 *         ];
 *     }
 * }
 * ```
 */
trait HasMultilingualValidation
{
    /**
     * Validation rules for a multilingual string field.
     * Accepts any Unicode letters including Arabic, Chinese, Cyrillic, etc.
     *
     * @param  bool  $required  Whether the field is required
     * @param  int  $max  Maximum length (default: 255)
     * @param  int|null  $min  Minimum length (optional)
     * @return array Validation rules array
     */
    protected function multilingualString(bool $required = true, int $max = 255, ?int $min = null): array
    {
        $rules = [
            $required ? 'required' : 'nullable',
            'string',
            "max:$max",
        ];

        if ($min !== null) {
            $rules[] = "min:$min";
        }

        return array_filter($rules);
    }

    /**
     * Validation rules for a multilingual text field (longer content).
     *
     * @param  bool  $required  Whether the field is required
     * @param  int  $max  Maximum length (default: 5000)
     * @return array Validation rules array
     */
    protected function multilingualText(bool $required = false, int $max = 5000): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            "max:$max",
        ];
    }

    /**
     * Validation rules for codes that accept letters from any script.
     * Uses Unicode letter validation (\\p{L}) + Unicode marks (\\p{M}) with /u flag.
     *
     * @param  bool  $required  Whether the field is required
     * @param  int|null  $length  Exact length (if specified)
     * @param  int|null  $max  Maximum length (if length not specified)
     * @return array Validation rules array
     */
    protected function unicodeLettersOnly(bool $required = true, ?int $length = null, ?int $max = null): array
    {
        $rules = [
            $required ? 'required' : 'nullable',
            'string',
            'regex:/^[\p{L}\p{M}]+$/u', // Letters + marks (diacritics) from any script
        ];

        if ($length !== null) {
            $rules[] = "size:$length";
        } elseif ($max !== null) {
            $rules[] = "max:$max";
        }

        return array_filter($rules);
    }

    /**
     * Validation rules for alphanumeric codes (letters + numbers from any script).
     * Supports Arabic-Indic numerals, Latin letters, Arabic letters, etc.
     *
     * @param  bool  $required  Whether the field is required
     * @param  int|null  $length  Exact length (if specified)
     * @param  int|null  $max  Maximum length (if length not specified)
     * @return array Validation rules array
     */
    protected function alphanumericCode(bool $required = true, ?int $length = null, ?int $max = null): array
    {
        $rules = [
            $required ? 'required' : 'nullable',
            'string',
            'regex:/^[\p{L}\p{M}\p{N}]+$/u', // Letters + marks + numbers from any script
        ];

        if ($length !== null) {
            $rules[] = "size:$length";
        } elseif ($max !== null) {
            $rules[] = "max:$max";
        }

        return array_filter($rules);
    }

    /**
     * Validation rules for codes with letters, numbers, and common separators.
     * Allows hyphens, underscores, and spaces in addition to Unicode letters and numbers.
     *
     * @param  bool  $required  Whether the field is required
     * @param  int  $max  Maximum length
     * @return array Validation rules array
     */
    protected function flexibleCode(bool $required = true, int $max = 50): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'regex:/^[\p{L}\p{M}\p{N}\s_-]+$/u', // Letters + marks + numbers + space + underscore + hyphen
            "max:$max",
        ];
    }

    /**
     * Validation rules for Arabic-specific name fields.
     *
     * @param  bool  $required  Whether the field is required
     * @param  int  $max  Maximum length
     * @return array Validation rules array
     */
    protected function arabicName(bool $required = false, int $max = 255): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'regex:/^[\p{Arabic}\s]+$/u', // Arabic letters + spaces
            "max:$max",
        ];
    }

    /**
     * Get validation rules for a field that should accept any Unicode text.
     * This is the most permissive option - use for fields like notes, descriptions, etc.
     *
     * @param  bool  $required  Whether the field is required
     * @param  int  $max  Maximum length
     * @return array Validation rules array
     */
    protected function unicodeText(bool $required = false, int $max = 5000): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            "max:$max",
        ];
    }
}
