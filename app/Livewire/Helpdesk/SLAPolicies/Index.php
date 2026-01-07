<?php

declare(strict_types=1);

namespace App\Livewire\Helpdesk\SLAPolicies;

use App\Models\TicketSLAPolicy;
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
        $policy = TicketSLAPolicy::findOrFail($id);

        if ($policy->tickets()->exists() || $policy->categories()->exists()) {
            session()->flash('error', __('Cannot delete SLA policy in use'));

            return;
        }

        $policy->delete();
        session()->flash('success', __('SLA Policy deleted successfully'));
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $policy = TicketSLAPolicy::findOrFail($id);
        $policy->is_active = ! $policy->is_active;
        $policy->updated_by = auth()->id();
        $policy->save();

        session()->flash('success', __('SLA Policy status updated'));
    }

    public function render()
    {
        $policies = TicketSLAPolicy::withCount(['tickets', 'categories'])
            ->paginate(20);

        $daysOfWeek = [
            0 => __('Sunday'),
            1 => __('Monday'),
            2 => __('Tuesday'),
            3 => __('Wednesday'),
            4 => __('Thursday'),
            5 => __('Friday'),
            6 => __('Saturday'),
        ];

        return view('livewire.helpdesk.sla-policies.index', [
            'policies' => $policies,
            'daysOfWeek' => $daysOfWeek,
        ]);
    }
}
