<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface PrintingServiceInterface
{
    public function renderHtml(string $view, array $data = []): string;

    /** @return array{path:string, mime:string} */
    public function renderPdfOrHtml(string $view, array $data, string $filename): array;
}
