<?php

declare(strict_types=1);

namespace App\Livewire\Rentals\Properties;

use App\Models\Property;
use App\Models\RentalUnit;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $address = '';

    public string $notes = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function openModal(?int $id = null): void
    {
        if ($id) {
            $this->authorize('rentals.manage');
        } else {
            $this->authorize('rentals.create');
        }

        $this->resetForm();

        if ($id) {
            $property = Property::findOrFail($id);
            $this->editingId = $id;
            $this->name = $property->name;
            $this->address = $property->address ?? '';
            $this->notes = $property->notes ?? '';
        }

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
        $this->name = '';
        $this->address = '';
        $this->notes = '';
        $this->resetErrorBag();
    }

    public function save(): void
    {
        if ($this->editingId) {
            $this->authorize('rentals.manage');
        } else {
            $this->authorize('rentals.create');
        }

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
        ]);

        $user = auth()->user();
        $data = array_merge($validated, [
            'branch_id' => $user->branch_id ?? 1,
        ]);

        if ($this->editingId) {
            Property::findOrFail($this->editingId)->update($data);
            session()->flash('success', __('Property updated successfully'));
        } else {
            Property::create($data);
            session()->flash('success', __('Property created successfully'));
        }

        Cache::forget('properties_stats_'.($user->branch_id ?? 'all'));
        $this->closeModal();
    }

    public function delete(int $id): void
    {
        $this->authorize('rentals.manage');

        Property::findOrFail($id)->delete();
        Cache::forget('properties_stats_'.(auth()->user()?->branch_id ?? 'all'));
        session()->flash('success', __('Property deleted successfully'));
    }

    public function getStatistics(): array
    {
        $user = auth()->user();
        $cacheKey = 'properties_stats_'.($user?->branch_id ?? 'all');

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $propertyQuery = Property::query();

            if ($user && $user->branch_id) {
                $propertyQuery->where('branch_id', $user->branch_id);
            }

            $propertyIds = Property::query()
                ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->pluck('id');

            $totalUnits = RentalUnit::whereIn('property_id', $propertyIds)->count();
            $availableUnits = RentalUnit::whereIn('property_id', $propertyIds)->where('status', 'available')->count();
            $occupiedUnits = RentalUnit::whereIn('property_id', $propertyIds)->where('status', 'occupied')->count();

            return [
                'total_properties' => $propertyQuery->count(),
                'total_units' => $totalUnits,
                'available_units' => $availableUnits,
                'occupied_units' => $occupiedUnits,
            ];
        });
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = auth()->user();

        $properties = Property::query()
            ->withCount('units')
            ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('address', 'like', "%{$this->search}%");
            }))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        $stats = $this->getStatistics();

        return view('livewire.rentals.properties.index', [
            'properties' => $properties,
            'stats' => $stats,
        ]);
    }
}
