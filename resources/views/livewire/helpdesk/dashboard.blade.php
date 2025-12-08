<div class="space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-800">{{ __('Helpdesk Dashboard') }}</h1>
        <p class="text-sm text-slate-500">{{ __('Overview of helpdesk metrics and performance') }}</p>
    </div>

    {{-- Overall Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">{{ __('Total') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['total']) }}</p>
                </div>
                <svg class="w-8 h-8 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
            </div>
        </div>
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-amber-100 text-sm">{{ __('Open') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['open']) }}</p>
                </div>
                <svg class="w-8 h-8 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm">{{ __('Pending') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['pending']) }}</p>
                </div>
                <svg class="w-8 h-8 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm">{{ __('Overdue') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['overdue']) }}</p>
                </div>
                <svg class="w-8 h-8 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
        </div>
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-sm">{{ __('Resolved') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['resolved']) }}</p>
                </div>
                <svg class="w-8 h-8 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>

    {{-- My Statistics --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('My Tickets') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="border-l-4 border-blue-500 pl-4">
                <p class="text-sm text-slate-500">{{ __('Assigned to Me') }}</p>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($myStats['total']) }}</p>
            </div>
            <div class="border-l-4 border-amber-500 pl-4">
                <p class="text-sm text-slate-500">{{ __('Avg Response Time') }}</p>
                <p class="text-2xl font-bold text-slate-900">{{ $myStats['avg_response_time'] ? number_format($myStats['avg_response_time']) . 'm' : '-' }}</p>
            </div>
            <div class="border-l-4 border-emerald-500 pl-4">
                <p class="text-sm text-slate-500">{{ __('Avg Resolution Time') }}</p>
                <p class="text-2xl font-bold text-slate-900">{{ $myStats['avg_resolution_time'] ? number_format($myStats['avg_resolution_time']) . 'm' : '-' }}</p>
            </div>
            <div class="border-l-4 border-red-500 pl-4">
                <p class="text-sm text-slate-500">{{ __('Unassigned') }}</p>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($stats['unassigned']) }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Tickets --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('Recent Tickets') }}</h2>
            <div class="space-y-3">
                @forelse($recentTickets as $ticket)
                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg hover:bg-slate-100">
                        <div class="flex-1">
                            <a href="{{ route('helpdesk.show', $ticket->id) }}" class="font-medium text-slate-900 hover:text-blue-600">
                                {{ $ticket->ticket_number }}
                            </a>
                            <p class="text-sm text-slate-600 truncate">{{ Str::limit($ticket->subject, 40) }}</p>
                        </div>
                        <span class="px-2 py-1 text-xs font-semibold rounded 
                            @if($ticket->status === 'new') bg-blue-100 text-blue-800
                            @elseif($ticket->status === 'open') bg-amber-100 text-amber-800
                            @else bg-slate-100 text-slate-800
                            @endif">
                            {{ __(ucfirst($ticket->status)) }}
                        </span>
                    </div>
                @empty
                    <p class="text-center text-slate-500 py-8">{{ __('No recent tickets') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Overdue Tickets --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('Overdue Tickets') }}</h2>
            <div class="space-y-3">
                @forelse($overdueTickets as $ticket)
                    <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg hover:bg-red-100">
                        <div class="flex-1">
                            <a href="{{ route('helpdesk.show', $ticket->id) }}" class="font-medium text-slate-900 hover:text-blue-600">
                                {{ $ticket->ticket_number }}
                            </a>
                            <p class="text-sm text-slate-600 truncate">{{ Str::limit($ticket->subject, 40) }}</p>
                            <p class="text-xs text-red-600">{{ __('Due') }}: {{ $ticket->due_date->diffForHumans() }}</p>
                        </div>
                        @if($ticket->assignedAgent)
                            <span class="text-xs text-slate-600">{{ $ticket->assignedAgent->name }}</span>
                        @else
                            <span class="text-xs text-red-600">{{ __('Unassigned') }}</span>
                        @endif
                    </div>
                @empty
                    <p class="text-center text-slate-500 py-8">{{ __('No overdue tickets') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Unassigned Tickets --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('Unassigned Tickets') }}</h2>
        <div class="space-y-3">
            @forelse($unassignedTickets as $ticket)
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg hover:bg-slate-100">
                    <div class="flex-1">
                        <a href="{{ route('helpdesk.show', $ticket->id) }}" class="font-medium text-slate-900 hover:text-blue-600">
                            {{ $ticket->ticket_number }}
                        </a>
                        <p class="text-sm text-slate-600">{{ Str::limit($ticket->subject, 60) }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        @if($ticket->priority)
                            <span class="px-2 py-1 text-xs font-semibold rounded" style="background-color: {{ $ticket->priority->color }}20; color: {{ $ticket->priority->color }}">
                                {{ $ticket->priority->name }}
                            </span>
                        @endif
                        <span class="text-xs text-slate-500">{{ $ticket->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            @empty
                <p class="text-center text-slate-500 py-8">{{ __('No unassigned tickets') }}</p>
            @endforelse
        </div>
    </div>
</div>
