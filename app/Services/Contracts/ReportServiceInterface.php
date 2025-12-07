<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface ReportServiceInterface
{
    public function financeSummary(int $branchId, ?string $from = null, ?string $to = null): array;

    public function topProducts(int $branchId, int $limit = 10): array;
}
