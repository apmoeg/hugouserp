<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\PosSession;
use App\Models\Sale;
use App\Models\User;

interface POSServiceInterface
{
    /** @param array{items:array<int,array{product_id:int,qty:float,price?:float,discount?:float,percent?:bool,tax_id?:int}>, customer_id?:int} $payload */
    public function checkout(array $payload): Sale;

    public function openSession(int $branchId, int $userId, float $openingCash = 0): PosSession;

    public function closeSession(int $sessionId, float $closingCash, ?string $notes = null): PosSession;

    public function getCurrentSession(int $branchId, int $userId): ?PosSession;

    /** @return array{session:PosSession,sales:\Illuminate\Database\Eloquent\Collection,summary:array} */
    public function getSessionReport(int $sessionId): array;

    public function validateDiscount(User $user, float $discountPercent): bool;
}
