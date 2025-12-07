<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Branches;

use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        // Livewire بيعمل Reset للـ Pagination لما الـ search تتغير
        $this->resetPage();
    }

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('branches.view')) {
            abort(403);
        }
    }

    public function render()
    {
        $query = Branch::query()
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';

                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term);
                });
            })
            ->orderBy('name');

        $branches = $query->paginate(15);

        return view('livewire.admin.branches.index', [
            'branches' => $branches,
        ]);
    }
}
