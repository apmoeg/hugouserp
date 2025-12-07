<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait WithInfiniteScroll
{
    public int $perPage = 15;

    public int $page = 1;

    public bool $hasMorePages = true;

    public bool $isLoading = false;

    public function initializeWithInfiniteScroll(): void
    {
        $this->perPage = $this->getDefaultPerPage();
    }

    protected function getDefaultPerPage(): int
    {
        return 15;
    }

    public function loadMore(): void
    {
        if ($this->hasMorePages && ! $this->isLoading) {
            $this->isLoading = true;
            $this->page++;
            $this->isLoading = false;
        }
    }

    public function resetInfiniteScroll(): void
    {
        $this->page = 1;
        $this->hasMorePages = true;
    }

    protected function paginateForInfiniteScroll(Builder $query): Collection
    {
        $total = $query->count();
        $items = $query->skip(0)->take($this->page * $this->perPage)->get();

        $this->hasMorePages = $items->count() < $total;

        return $items;
    }

    protected function cursorPaginateForInfiniteScroll(Builder $query, string $orderColumn = 'id'): Collection
    {
        $items = $query->orderByDesc($orderColumn)
            ->take(($this->page * $this->perPage) + 1)
            ->get();

        $this->hasMorePages = $items->count() > ($this->page * $this->perPage);

        return $items->take($this->page * $this->perPage);
    }
}
