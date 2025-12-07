<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface BarcodeServiceInterface
{
    /**
     * Generate a valid EAN-13 code for a given seed value.
     */
    public function ean13(string $seed): string;

    /**
     * Store generated barcode representation and return storage info.
     *
     * @return array{path:string, code:string}
     */
    public function storeEan13(string $seed): array;
}
