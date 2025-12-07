<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface ModuleFieldServiceInterface
{
    /**
     * Get normalized form schema for a given module/entity and branch.
     *
     * @return array<int,array<string,mixed>>
     */
    public function formSchema(string $moduleKey, string $entity, ?int $branchId = null): array;

    /**
     * Get exportable dynamic field keys (column names) for CSV export.
     *
     * @return array<int,string>
     */
    public function exportColumns(string $moduleKey, string $entity, ?int $branchId = null): array;
}
