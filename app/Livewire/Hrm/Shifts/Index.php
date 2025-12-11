<?php

declare(strict_types=1);

namespace App\Livewire\Hrm\Shifts;

use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public ?string $status = null;
    public ?int $branchId = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('hrm.view')) {
            abort(403);
        }

        $this->branchId = $user->branch_id;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        if (! $user || ! $user->can('hrm.view')) {
            abort(403);
        }

        $query = Shift::query()
            ->with(['branch'])
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term);
                });
            })
            ->when($this->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name');

        $shifts = $query->paginate(20);

        return view('livewire.hrm.shifts.index', [
            'shifts' => $shifts,
        ]);
    }
}
