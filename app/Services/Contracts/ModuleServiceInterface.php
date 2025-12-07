<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Branch;
use App\Models\Module;

interface ModuleServiceInterface
{
    /** @return array<int, array{key:string,name:string,enabled:bool}> */
    public function allForBranch(?int $branchId = null): array;

    public function isEnabled(string $key, ?int $branchId = null): bool;

    /* ====== Extended module management API ====== */

    public function ensureModule(string $key, array $attributes = []): Module;

    public function enableForBranch(Branch $branch, string $moduleKey, array $settings = []): void;

    public function disableForBranch(Branch $branch, string $moduleKey): void;

    /** @return array<string, array{enabled:bool,settings:array}> */
    public function getBranchModulesConfig(Branch $branch): array;

    /** Get modules by type (data/functional/hybrid) */
    public function getModulesByType(string $type, ?int $branchId = null): array;

    /** Get navigation items for user based on permissions */
    public function getNavigationForUser($user, ?int $branchId = null): array;

    /** Check if user can perform operation */
    public function userCanPerformOperation($user, string $moduleKey, string $operationKey): bool;

    /** Get active policies for module and branch */
    public function getActivePolicies(int $moduleId, ?int $branchId = null): array;
}
