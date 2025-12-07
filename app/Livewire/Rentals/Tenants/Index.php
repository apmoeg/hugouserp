<?php

declare(strict_types=1);

namespace App\Livewire\Rentals\Tenants;

use App\Models\RentalContract;
use App\Models\Tenant;
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

    #[Url]
    public string $status = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public bool $is_active = true;

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
            $tenant = Tenant::findOrFail($id);
            $this->editingId = $id;
            $this->name = $tenant->name;
            $this->email = $tenant->email ?? '';
            $this->phone = $tenant->phone ?? '';
            $this->address = $tenant->address ?? '';
            $this->is_active = $tenant->is_active;
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
        $this->email = '';
        $this->phone = '';
        $this->address = '';
        $this->is_active = true;
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
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $user = auth()->user();
        $data = array_merge($validated, [
            'branch_id' => $user->branch_id ?? 1,
        ]);

        if ($this->editingId) {
            Tenant::findOrFail($this->editingId)->update($data);
            session()->flash('success', __('Tenant updated successfully'));
        } else {
            Tenant::create($data);
            session()->flash('success', __('Tenant created successfully'));
        }

        Cache::forget('tenants_stats_'.($user->branch_id ?? 'all'));
        $this->closeModal();
    }

    public function delete(int $id): void
    {
        $this->authorize('rentals.manage');

        Tenant::findOrFail($id)->delete();
        Cache::forget('tenants_stats_'.(auth()->user()?->branch_id ?? 'all'));
        session()->flash('success', __('Tenant deleted successfully'));
    }

    public function getStatistics(): array
    {
        $user = auth()->user();
        $cacheKey = 'tenants_stats_'.($user?->branch_id ?? 'all');

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $tenantQuery = Tenant::query();

            if ($user && $user->branch_id) {
                $tenantQuery->where('branch_id', $user->branch_id);
            }

            $activeContracts = RentalContract::query()
                ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->where('status', 'active')
                ->count();

            return [
                'total_tenants' => $tenantQuery->count(),
                'active_tenants' => Tenant::query()
                    ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                    ->where('is_active', true)->count(),
                'active_contracts' => $activeContracts,
                'inactive_tenants' => Tenant::query()
                    ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                    ->where('is_active', false)->count(),
            ];
        });
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = auth()->user();

        $tenants = Tenant::query()
            ->withCount('contracts')
            ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%");
            }))
            ->when($this->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        $stats = $this->getStatistics();

        return view('livewire.rentals.tenants.index', [
            'tenants' => $tenants,
            'stats' => $stats,
        ]);
    }
}
