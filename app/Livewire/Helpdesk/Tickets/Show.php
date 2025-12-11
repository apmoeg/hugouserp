<?php

declare(strict_types=1);

namespace App\Livewire\Helpdesk\Tickets;

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Show extends Component
{
    public Ticket $ticket;
    public string $replyMessage = '';
    public bool $isInternal = false;

    protected function rules(): array
    {
        return [
            'replyMessage' => ['required', 'string', 'min:3'],
            'isInternal' => ['boolean'],
        ];
    }

    public function mount(Ticket $ticket): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('helpdesk.view')) {
            abort(403);
        }

        $this->ticket = $ticket->load([
            'customer',
            'assignedAgent',
            'category',
            'priority',
            'slaPolicy',
            'branch',
            'replies.user',
            'creator',
        ]);
    }

    public function addReply(): void
    {
        $this->validate();

        $user = Auth::user();

        $this->ticket->addReply($this->replyMessage, $user->id, $this->isInternal);

        $this->replyMessage = '';
        $this->isInternal = false;

        $this->ticket->refresh();

        session()->flash('message', 'Reply added successfully.');
    }

    public function assignToMe(): void
    {
        $user = Auth::user();

        if (! $user->can('helpdesk.manage')) {
            abort(403);
        }

        $this->ticket->assign($user->id);
        $this->ticket->refresh();

        session()->flash('message', 'Ticket assigned to you.');
    }

    public function resolve(): void
    {
        if (! Auth::user()->can('helpdesk.manage')) {
            abort(403);
        }

        $this->ticket->resolve();
        $this->ticket->refresh();

        session()->flash('message', 'Ticket marked as resolved.');
    }

    public function close(): void
    {
        if (! Auth::user()->can('helpdesk.manage')) {
            abort(403);
        }

        if (! $this->ticket->canBeClosed()) {
            session()->flash('error', 'Ticket must be resolved before closing.');
            return;
        }

        $this->ticket->close();
        $this->ticket->refresh();

        session()->flash('message', 'Ticket closed.');
    }

    public function reopen(): void
    {
        if (! Auth::user()->can('helpdesk.manage')) {
            abort(403);
        }

        if (! $this->ticket->canBeReopened()) {
            session()->flash('error', 'Ticket cannot be reopened.');
            return;
        }

        $this->ticket->reopen();
        $this->ticket->refresh();

        session()->flash('message', 'Ticket reopened.');
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.helpdesk.tickets.show');
    }
}
