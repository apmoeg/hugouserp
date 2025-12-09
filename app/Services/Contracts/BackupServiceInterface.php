<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface BackupServiceInterface
{
    /** @return array{path: string, size: int} */
    public function run(bool $verify = true): array;

    /** @return array<int, array{path:string,size:int,modified:int}> */
    public function list(): array;

    public function delete(string $path): bool;

    public function verify(array $result): bool;
}
