<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface QRServiceInterface
{
    /** @return array{path:string, mime:string} */
    public function make(string $payload, string $filename): array;
}
