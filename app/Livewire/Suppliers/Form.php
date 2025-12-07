<?php

declare(strict_types=1);

namespace App\Livewire\Suppliers;

use App\Livewire\Concerns\HandlesErrors;
use App\Models\Supplier;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;
    use HandlesErrors;

    public ?Supplier $supplier = null;

    public bool $editMode = false;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public string $city = '';

    public string $country = '';

    public string $tax_number = '';

    public string $company_name = '';

    public string $contact_person = '';

    public string $notes = '';

    public bool $is_active = true;

    protected function rules(): array
    {
        $supplierId = $this->supplier?->id;
        $branchId = auth()->user()?->branches()->first()?->id;

        return [
            'name' => ['required', 'string', 'max:255', 'min:2'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')
                    ->where('branch_id', $branchId)
                    ->ignore($supplierId),
            ],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^[\d\s\+\-\(\)]+$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'tax_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('suppliers', 'tax_number')
                    ->where('branch_id', $branchId)
                    ->ignore($supplierId),
            ],
            'company_name' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
    }

    public function mount(?Supplier $supplier = null): void
    {
        $this->authorize('suppliers.manage');

        if ($supplier && $supplier->exists) {
            $this->supplier = $supplier;
            $this->editMode = true;
            $this->fill($supplier->toArray());
        }
    }

    public function save(): void
    {
        $validated = $this->validate();
        $validated['branch_id'] = auth()->user()->branches()->first()?->id;
        $validated['created_by'] = auth()->id();

        $this->handleOperation(
            operation: function () use ($validated) {
                if ($this->editMode) {
                    $this->supplier->update($validated);
                } else {
                    Supplier::create($validated);
                }
            },
            successMessage: $this->editMode ? __('Supplier updated successfully') : __('Supplier created successfully'),
            redirectRoute: 'suppliers.index'
        );
    }

    public function render()
    {
        return view('livewire.suppliers.form')
            ->layout('layouts.app', ['title' => $this->editMode ? __('Edit Supplier') : __('Add Supplier')]);
    }
}
