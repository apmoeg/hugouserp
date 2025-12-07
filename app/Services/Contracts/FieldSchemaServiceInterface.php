<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use Illuminate\Contracts\Validation\Validator;

interface FieldSchemaServiceInterface
{
    /** @return array<int, array{name:string,type:string,rules:array,options?:array}> */
    public function for(string $module, ?int $branchId = null): array;

    public function validate(string $module, array $payload, ?int $branchId = null): Validator;

    public function filter(string $module, array $payload, ?int $branchId = null): array;
}
