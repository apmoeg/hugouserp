<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Currency;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class CurrencyManager extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public bool $showModal = false;

    public string $code = '';

    public string $name = '';

    public string $nameAr = '';

    public string $symbol = '';

    public int $decimalPlaces = 2;

    public int $sortOrder = 0;

    public bool $isActive = true;

    public bool $isBase = false;

    protected CurrencyService $currencyService;

    public function boot(CurrencyService $currencyService): void
    {
        $this->currencyService = $currencyService;
    }

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('settings.currency.manage')) {
            abort(403);
        }
    }

    public function render()
    {
        $currencies = Currency::orderBy('sort_order')->orderBy('code')->paginate(20);

        return view('livewire.admin.currency-manager', [
            'currencies' => $currencies,
        ]);
    }

    public function openModal(): void
    {
        $this->resetForm();
        $this->sortOrder = Currency::max('sort_order') + 1;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->code = '';
        $this->name = '';
        $this->nameAr = '';
        $this->symbol = '';
        $this->decimalPlaces = 2;
        $this->sortOrder = 0;
        $this->isActive = true;
        $this->isBase = false;
        $this->resetValidation();
    }

    public function edit(int $id): void
    {
        $currency = Currency::find($id);
        if ($currency) {
            $this->editingId = $id;
            $this->code = $currency->code;
            $this->name = $currency->name;
            $this->nameAr = $currency->name_ar ?? '';
            $this->symbol = $currency->symbol;
            $this->decimalPlaces = $currency->decimal_places;
            $this->sortOrder = $currency->sort_order;
            $this->isActive = $currency->is_active;
            $this->isBase = $currency->is_base;
            $this->showModal = true;
        }
    }

    public function save(): void
    {
        $rules = [
            'code' => ['required', 'string', 'size:3', 'alpha', $this->editingId ? 'unique:currencies,code,'.$this->editingId : 'unique:currencies,code'],
            'name' => ['required', 'string', 'max:100'],
            'nameAr' => ['nullable', 'string', 'max:100'],
            'symbol' => ['required', 'string', 'max:10'],
            'decimalPlaces' => ['required', 'integer', 'min:0', 'max:6'],
            'sortOrder' => ['required', 'integer', 'min:0'],
        ];

        $this->validate($rules);

        $data = [
            'code' => strtoupper($this->code),
            'name' => $this->name,
            'name_ar' => $this->nameAr ?: null,
            'symbol' => $this->symbol,
            'decimal_places' => $this->decimalPlaces,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            $currency = Currency::find($this->editingId);
            if ($currency) {
                if ($this->isBase && ! $currency->is_base) {
                    Currency::where('is_base', true)->update(['is_base' => false]);
                    $data['is_base'] = true;
                } elseif (! $this->isBase && $currency->is_base) {
                    $this->dispatch('notify', type: 'error', message: __('Cannot unset base currency. Set another currency as base first.'));

                    return;
                }

                $currency->update($data);
                $this->dispatch('notify', type: 'success', message: __('Currency updated successfully'));
            }
        } else {
            $data['created_by'] = auth()->id();

            if ($this->isBase) {
                Currency::where('is_base', true)->update(['is_base' => false]);
                $data['is_base'] = true;
            }

            Currency::create($data);
            $this->dispatch('notify', type: 'success', message: __('Currency created successfully'));
        }

        $this->currencyService->clearCurrencyCache();
        $this->closeModal();
    }

    public function toggleActive(int $id): void
    {
        $currency = Currency::find($id);
        if ($currency) {
            if ($currency->is_base && $currency->is_active) {
                $this->dispatch('notify', type: 'error', message: __('Cannot deactivate base currency'));

                return;
            }

            $currency->is_active = ! $currency->is_active;
            $currency->save();

            $this->currencyService->clearCurrencyCache();
            $this->dispatch('notify', type: 'success', message: $currency->is_active ? __('Currency activated') : __('Currency deactivated'));
        }
    }

    public function setAsBase(int $id): void
    {
        $currency = Currency::find($id);
        if ($currency) {
            Currency::where('is_base', true)->update(['is_base' => false]);
            $currency->is_base = true;
            $currency->is_active = true;
            $currency->save();

            $this->currencyService->clearCurrencyCache();
            $this->dispatch('notify', type: 'success', message: __(':currency is now the base currency', ['currency' => $currency->code]));
        }
    }

    public function delete(int $id): void
    {
        $currency = Currency::find($id);
        if ($currency) {
            if ($currency->is_base) {
                $this->dispatch('notify', type: 'error', message: __('Cannot delete base currency'));

                return;
            }

            $currency->delete();
            $this->currencyService->clearCurrencyCache();
            $this->dispatch('notify', type: 'success', message: __('Currency deleted successfully'));
        }
    }
}
