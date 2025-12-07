<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface BackupServiceInterface
{
    public function run(bool $verify = true): string;

    /** @return array<int, array{path:string,size:int,modified:int}> */
    public function list(): array;

    public function delete(string $path): bool;
}
