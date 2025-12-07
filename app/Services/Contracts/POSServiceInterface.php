<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Sale;

interface POSServiceInterface
{
    /** @param array{items:array<int,array{product_id:int,qty:float,price?:float,discount?:float,percent?:bool,tax_id?:int}>, customer_id?:int} $payload */
    public function checkout(array $payload): Sale;
}
