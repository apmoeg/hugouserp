<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentTagStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('documents.manage') || $this->user()->can('documents.tags.create');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:document_tags,name'],
            'color' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Tag name is required'),
            'name.unique' => __('A tag with this name already exists'),
            'name.max' => __('Tag name cannot exceed 100 characters'),
            'color.max' => __('Color code cannot exceed 20 characters'),
            'description.max' => __('Description cannot exceed 500 characters'),
            'branch_id.exists' => __('Selected branch does not exist'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => __('Tag Name'),
            'color' => __('Color'),
            'description' => __('Description'),
            'branch_id' => __('Branch'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set branch_id if not provided
        if (!$this->has('branch_id') && $this->user()->branch_id) {
            $this->merge([
                'branch_id' => $this->user()->branch_id,
            ]);
        }
    }
}
