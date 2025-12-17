<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Layout('layouts.admin')]
class ActivityLog extends Component
{
    use WithPagination;

    public string $search = '';
    public string $logType = '';
    public string $eventType = '';
    public string $causerType = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public int $perPage = 25;

    protected $queryString = [
        'search' => ['except' => ''],
        'logType' => ['except' => ''],
        'eventType' => ['except' => ''],
        'causerType' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingLogType(): void
    {
        $this->resetPage();
    }

    public function updatingEventType(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'logType', 'eventType', 'causerType', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function getLogTypes(): array
    {
        return Cache::remember('activity_log_types', 300, function () {
            return Activity::distinct()->pluck('log_name')->filter()->toArray();
        });
    }

    public function getEventTypes(): array
    {
        return ['created', 'updated', 'deleted', 'restored', 'login', 'logout', 'exported', 'imported'];
    }

    public function render(): View
    {
        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('description', 'like', '%' . $this->search . '%')
                      ->orWhere('properties', 'like', '%' . $this->search . '%')
                      ->orWhere('subject_type', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->logType, fn($q) => $q->where('log_name', $this->logType))
            ->when($this->eventType, fn($q) => $q->where('event', $this->eventType))
            ->when($this->causerType, fn($q) => $q->where('causer_type', $this->causerType))
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.activity-log', [
            'activities' => $activities,
            'logTypes' => $this->getLogTypes(),
            'eventTypes' => $this->getEventTypes(),
        ]);
    }
}
