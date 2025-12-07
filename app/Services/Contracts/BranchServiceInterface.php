<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface BranchServiceInterface
{
    public function currentId(): ?int;

    public function current(): ?Branch;

    /** @return Collection<int, Branch> */
    public function forUser(?User $user = null): Collection;

    public function ensureAccess(Branch $branch, ?User $user = null): void;

    /* ====== Extended branch management API ====== */

    /** @return Collection<int, Branch> */
    public function list(array $filters = []): Collection;

    public function create(array $data): Branch;

    public function update(Branch $branch, array $data): Branch;

    public function toggleActive(Branch $branch, ?bool $active = null): Branch;

    /**
     * Sync modules for a branch using payload like:
     * [
     *   ['module_key' => 'pos', 'enabled' => true, 'settings' => []],
     * ]
     */
    public function syncModules(Branch $branch, array $modulesPayload): void;

    public function attachUser(Branch $branch, User $user): void;

    public function detachUser(Branch $branch, User $user): void;
}
