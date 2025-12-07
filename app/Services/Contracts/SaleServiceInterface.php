<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\ReturnNote;
use App\Models\Sale;

interface SaleServiceInterface
{
    public function show(int $id): Sale;

    public function handleReturn(int $saleId, array $items, ?string $reason = null): ReturnNote;

    public function voidSale(int $saleId, ?string $reason = null): Sale;

    /** @return array{path:string, mime:string} */
    public function printInvoice(int $saleId): array;
}
