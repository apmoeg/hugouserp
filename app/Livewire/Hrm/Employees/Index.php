<?php

declare(strict_types=1);

namespace App\Livewire\Hrm\Employees;

use App\Models\HREmployee;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    /**
     * Simple text search across code, name, position and linked user.
     */
    public ?string $search = '';

    /**
     * Filter by active / inactive employees.
     *
     * @var null|"active"|"inactive"
     */
    public ?string $status = null;

    /**
     * Current branch scope.
     */
    public ?int $branchId = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('hrm.employees.view')) {
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

        if (! $user || ! $user->can('hrm.employees.view')) {
            abort(403);
        }

        $query = HREmployee::query()
            ->with(['branch', 'user'])
            ->when($this->branchId, function ($q) {
                $q->where('branch_id', $this->branchId);
            })
            ->when($this->search !== null && $this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';

                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhere('position', 'like', $term)
                        ->orWhereHas('user', function ($userQuery) use ($term) {
                            $userQuery->where('name', 'like', $term)
                                ->orWhere('email', 'like', $term)
                                ->orWhere('username', 'like', $term);
                        });
                });
            })
            ->when($this->status === 'active', function ($q) {
                $q->where('is_active', true);
            })
            ->when($this->status === 'inactive', function ($q) {
                $q->where('is_active', false);
            })
            ->orderByDesc('id');

        $employees = $query->paginate(15);

        return view('livewire.hrm.employees.index', [
            'employees' => $employees,
        ]);
    }
}
