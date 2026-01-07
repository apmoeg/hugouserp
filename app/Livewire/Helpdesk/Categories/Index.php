<?php

declare(strict_types=1);

namespace App\Livewire\Helpdesk\Categories;

use App\Models\TicketCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('helpdesk.manage');
    }

    public function delete(int $id): void
    {
        $category = TicketCategory::findOrFail($id);

        if ($category->hasChildren()) {
            session()->flash('error', __('Cannot delete category with subcategories'));

            return;
        }

        if ($category->tickets()->exists()) {
            session()->flash('error', __('Cannot delete category with existing tickets'));

            return;
        }

        $category->delete();
        session()->flash('success', __('Category deleted successfully'));
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $category = TicketCategory::findOrFail($id);
        $category->is_active = ! $category->is_active;
        $category->updated_by = auth()->id();
        $category->save();

        session()->flash('success', __('Category status updated'));
    }

    public function render()
    {
        $categories = TicketCategory::with(['parent', 'defaultAssignee', 'slaPolicy'])
            ->withCount('tickets')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.helpdesk.categories.index', [
            'categories' => $categories,
        ]);
    }
}
