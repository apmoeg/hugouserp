<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Purchase;

interface PurchaseServiceInterface
{
    public function create(array $payload): Purchase;

    public function approve(int $id): Purchase;

    public function receive(int $id): Purchase;

    public function pay(int $id, float $amount): Purchase;

    public function cancel(int $id): Purchase;
}
