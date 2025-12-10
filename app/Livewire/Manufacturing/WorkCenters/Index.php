<?php

declare(strict_types=1);

namespace App\Livewire\Manufacturing\WorkCenters;

use App\Models\WorkCenter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

    public function mount(): void
    {
        $this->authorize('manufacturing.view');
    }

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

    public function getStatistics(): array
    {
        $user = auth()->user();
        $cacheKey = 'work_centers_stats_'.($user?->branch_id ?? 'all');

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $query = WorkCenter::query();

            if ($user && $user->branch_id) {
                $query->where('branch_id', $user->branch_id);
            }

            return [
                'total_centers' => $query->count(),
                'active_centers' => $query->where('status', 'active')->count(),
                'total_capacity' => $query->sum('capacity_per_hour'),
                'avg_cost_per_hour' => $query->avg('cost_per_hour'),
            ];
        });
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = auth()->user();

        $workCenters = WorkCenter::query()
            ->with(['branch'])
            ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('code', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%")
                    ->orWhere('name_ar', 'like', "%{$this->search}%");
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        $stats = $this->getStatistics();

        return view('livewire.manufacturing.work-centers.index', [
            'workCenters' => $workCenters,
            'stats' => $stats,
        ]);
    }
}
