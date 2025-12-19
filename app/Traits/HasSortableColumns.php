<?php

declare(strict_types=1);

namespace App\Traits;

/**
 * Trait for safely handling sortable columns in Livewire components.
 * Prevents SQL injection by whitelisting allowed sort columns and directions.
 * 
 * Classes using this trait MUST define these properties:
 *   public string $sortField = 'your_default';
 *   public string $sortDirection = 'desc';
 */
trait HasSortableColumns
{
    /**
     * Get the list of allowed sortable columns.
     * Override this method in the component to define allowed columns.
     *
     * @return array<string>
     */
    protected function allowedSortColumns(): array
    {
        return ['id', 'created_at', 'updated_at'];
    }

    /**
     * Get the default sort column.
     */
    protected function defaultSortColumn(): string
    {
        return 'created_at';
    }

    /**
     * Get the default sort direction.
     */
    protected function defaultSortDirection(): string
    {
        return 'desc';
    }

    /**
     * Sort by a given field with validation.
     */
    public function sortBy(string $field): void
    {
        // Validate field is in allowed list
        if (!in_array($field, $this->allowedSortColumns(), true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Get sanitized sort field - returns default if invalid.
     */
    protected function getSortField(): string
    {
        if (in_array($this->sortField, $this->allowedSortColumns(), true)) {
            return $this->sortField;
        }

        return $this->defaultSortColumn();
    }

    /**
     * Get sanitized sort direction - returns 'asc' or 'desc' only.
     */
    protected function getSortDirection(): string
    {
        return in_array(strtolower($this->sortDirection), ['asc', 'desc'], true)
            ? strtolower($this->sortDirection)
            : $this->defaultSortDirection();
    }

    /**
     * Apply sorting to a query builder with sanitized values.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  string|null  $tablePrefix  Optional table prefix for joins
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    protected function applySorting($query, ?string $tablePrefix = null)
    {
        $field = $this->getSortField();
        $direction = $this->getSortDirection();

        if ($tablePrefix) {
            $field = $tablePrefix.'.'.$field;
        }

        return $query->orderBy($field, $direction);
    }
}
