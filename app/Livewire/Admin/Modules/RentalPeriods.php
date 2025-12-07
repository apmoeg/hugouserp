<?php

namespace App\Livewire\Admin\Modules;

use App\Models\Module;
use App\Models\RentalPeriod;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class RentalPeriods extends Component
{
    use AuthorizesRequests;

    public Module $module;

    public bool $showModal = false;

    public bool $isEditing = false;

    public ?int $editingPeriodId = null;

    public string $period_key = '';

    public string $period_name = '';

    public string $period_name_ar = '';

    public string $period_type = 'monthly';

    public int $duration_value = 1;

    public string $duration_unit = 'months';

    public float $price_multiplier = 1;

    public bool $is_default = false;

    public bool $is_active = true;

    public int $sort_order = 0;

    protected $rules = [
        'period_key' => 'required|string|max:50|regex:/^[a-z_]+$/',
        'period_name' => 'required|string|max:100',
        'period_name_ar' => 'nullable|string|max:100',
        'period_type' => 'required|in:hourly,daily,weekly,monthly,quarterly,yearly,custom',
        'duration_value' => 'required|integer|min:1',
        'duration_unit' => 'required|in:hours,days,weeks,months,years',
        'price_multiplier' => 'required|numeric|min:0',
    ];

    public function mount(Module $module): void
    {
        $this->authorize('modules.manage');
        $this->module = $module;

        if (! $module->is_rental) {
            session()->flash('warning', __('This module is not configured for rentals'));
        }
    }

    public function openAddModal(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
        $maxOrder = RentalPeriod::where('module_id', $this->module->id)->max('sort_order') ?? 0;
        $this->sort_order = $maxOrder + 1;
    }

    public function openEditModal(int $periodId): void
    {
        $period = RentalPeriod::findOrFail($periodId);

        $this->editingPeriodId = $period->id;
        $this->period_key = $period->period_key;
        $this->period_name = $period->period_name;
        $this->period_name_ar = $period->period_name_ar ?? '';
        $this->period_type = $period->period_type;
        $this->duration_value = $period->duration_value;
        $this->duration_unit = $period->duration_unit;
        $this->price_multiplier = (float) $period->price_multiplier;
        $this->is_default = $period->is_default;
        $this->is_active = $period->is_active;
        $this->sort_order = $period->sort_order;

        $this->isEditing = true;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingPeriodId = null;
        $this->period_key = '';
        $this->period_name = '';
        $this->period_name_ar = '';
        $this->period_type = 'monthly';
        $this->duration_value = 1;
        $this->duration_unit = 'months';
        $this->price_multiplier = 1;
        $this->is_default = false;
        $this->is_active = true;
        $this->sort_order = 0;
    }

    public function save(): void
    {
        $this->authorize('modules.manage');
        $this->validate();

        $data = [
            'module_id' => $this->module->id,
            'period_key' => $this->period_key,
            'period_name' => $this->period_name,
            'period_name_ar' => $this->period_name_ar ?: null,
            'period_type' => $this->period_type,
            'duration_value' => $this->duration_value,
            'duration_unit' => $this->duration_unit,
            'price_multiplier' => $this->price_multiplier,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->is_default) {
            RentalPeriod::where('module_id', $this->module->id)
                ->where('id', '!=', $this->editingPeriodId)
                ->update(['is_default' => false]);
        }

        if ($this->isEditing && $this->editingPeriodId) {
            RentalPeriod::findOrFail($this->editingPeriodId)->update($data);
            session()->flash('success', __('Rental period updated successfully'));
        } else {
            RentalPeriod::create($data);
            session()->flash('success', __('Rental period created successfully'));
        }

        $this->closeModal();
    }

    public function delete(int $periodId): void
    {
        $this->authorize('modules.manage');
        RentalPeriod::findOrFail($periodId)->delete();
        session()->flash('success', __('Rental period deleted successfully'));
    }

    public function toggleActive(int $periodId): void
    {
        $this->authorize('modules.manage');
        $period = RentalPeriod::findOrFail($periodId);
        $period->update(['is_active' => ! $period->is_active]);
    }

    public function setDefault(int $periodId): void
    {
        $this->authorize('modules.manage');

        RentalPeriod::where('module_id', $this->module->id)->update(['is_default' => false]);
        RentalPeriod::findOrFail($periodId)->update(['is_default' => true]);

        session()->flash('success', __('Default period updated'));
    }

    public function render()
    {
        $periods = RentalPeriod::where('module_id', $this->module->id)
            ->orderBy('sort_order')
            ->orderBy('duration_value')
            ->get();

        return view('livewire.admin.modules.rental-periods', [
            'periods' => $periods,
            'periodTypes' => [
                'hourly' => __('Hourly'),
                'daily' => __('Daily'),
                'weekly' => __('Weekly'),
                'monthly' => __('Monthly'),
                'quarterly' => __('Quarterly'),
                'yearly' => __('Yearly'),
                'custom' => __('Custom'),
            ],
            'durationUnits' => [
                'hours' => __('Hours'),
                'days' => __('Days'),
                'weeks' => __('Weeks'),
                'months' => __('Months'),
                'years' => __('Years'),
            ],
        ])->layout('layouts.app');
    }
}
