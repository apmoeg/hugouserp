<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface WoodServiceInterface
{
    public function conversions(int $branchId): array;

    public function createConversion(array $payload): int;

    public function recalc(int $conversionId): void;

    public function listWaste(int $branchId): array;

    public function storeWaste(array $payload): int;
}
