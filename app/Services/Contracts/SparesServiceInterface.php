<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface SparesServiceInterface
{
    public function listCompatibility(int $productId): array;

    public function attach(int $productId, int $compatibleWithId): int;

    public function detach(int $productId, int $compatibleWithId): int;
}
