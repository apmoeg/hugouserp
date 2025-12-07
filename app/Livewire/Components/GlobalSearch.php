<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Services\GlobalSearchService;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $query = '';
    public ?string $selectedModule = null;
    public array $results = [];
    public array $groupedResults = [];
    public array $recentSearches = [];
    public bool $showResults = false;
    public int $totalResults = 0;

    protected $listeners = ['resetSearch' => 'reset'];

    public function mount(): void
    {
        $this->loadRecentSearches();
    }

    public function updatedQuery(): void
    {
        if (strlen($this->query) >= 2) {
            $this->performSearch();
        } else {
            $this->resetResults();
        }
    }

    public function updatedSelectedModule(): void
    {
        if (!empty($this->query)) {
            $this->performSearch();
        }
    }

    public function performSearch(): void
    {
        try {
            $searchService = app(GlobalSearchService::class);
            
            $result = $searchService->search(
                $this->query,
                auth()->user()->current_branch_id ?? null,
                $this->selectedModule,
                auth()->id()
            );

            $this->results = $result['results'];
            $this->groupedResults = $result['grouped'] ?? [];
            $this->totalResults = $result['count'];
            $this->showResults = true;

        } catch (\Exception $e) {
            $this->dispatch('error', message: __('Search failed: ') . $e->getMessage());
            $this->resetResults();
        }
    }

    public function selectResult(string $url): void
    {
        $this->redirect($url);
    }

    public function useRecentSearch(string $query): void
    {
        $this->query = $query;
        $this->performSearch();
    }

    public function clearHistory(): void
    {
        try {
            $searchService = app(GlobalSearchService::class);
            $searchService->clearHistory(auth()->id());
            $this->recentSearches = [];
            $this->dispatch('success', message: __('Search history cleared'));
        } catch (\Exception $e) {
            $this->dispatch('error', message: __('Failed to clear history'));
        }
    }

    public function resetResults(): void
    {
        $this->results = [];
        $this->groupedResults = [];
        $this->totalResults = 0;
        $this->showResults = false;
    }

    private function loadRecentSearches(): void
    {
        try {
            $searchService = app(GlobalSearchService::class);
            $this->recentSearches = $searchService->getRecentSearches(auth()->id(), 5);
        } catch (\Exception $e) {
            $this->recentSearches = [];
        }
    }

    public function getAvailableModulesProperty(): array
    {
        return app(GlobalSearchService::class)->getAvailableModules();
    }

    public function render()
    {
        return view('livewire.components.global-search');
    }
}
