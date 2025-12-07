<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\UserServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService implements UserServiceInterface
{
    use HandlesServiceErrors;

    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    public function getAllUsers(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->handleServiceOperation(
            callback: fn () => $this->userRepository->paginateWithFilters($filters, $perPage),
            operation: 'getAllUsers',
            context: ['filters' => $filters, 'per_page' => $perPage]
        );
    }

    public function getUserById(int $id): ?User
    {
        return $this->handleServiceOperation(
            callback: fn () => $this->userRepository->find($id),
            operation: 'getUserById',
            context: ['user_id' => $id],
            defaultValue: null
        );
    }

    public function createUser(array $data): User
    {
        return $this->handleServiceOperation(
            callback: function () use ($data) {
                return DB::transaction(function () use ($data) {
                    $userData = [
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => Hash::make($data['password']),
                        'phone' => $data['phone'] ?? null,
                        'username' => $data['username'] ?? null,
                        'branch_id' => $data['branch_id'] ?? null,
                        'is_active' => $data['is_active'] ?? true,
                        'locale' => $data['locale'] ?? 'en',
                        'timezone' => $data['timezone'] ?? 'UTC',
                    ];

                    /** @var User $user */
                    $user = $this->userRepository->create($userData);

                    if (! empty($data['roles'])) {
                        $this->userRepository->syncRoles($user, $data['roles']);
                    }

                    if (! empty($data['branches'])) {
                        $this->userRepository->syncBranches($user, $data['branches']);
                    }

                    $this->logServiceInfo('createUser', 'User created successfully', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                    ]);

                    return $user->fresh(['roles', 'branches', 'branch']);
                });
            },
            operation: 'createUser',
            context: ['email' => $data['email'] ?? null]
        );
    }

    public function updateUser(User $user, array $data): User
    {
        return $this->handleServiceOperation(
            callback: function () use ($user, $data) {
                return DB::transaction(function () use ($user, $data) {
                    $userData = array_filter([
                        'name' => $data['name'] ?? null,
                        'email' => $data['email'] ?? null,
                        'phone' => $data['phone'] ?? null,
                        'username' => $data['username'] ?? null,
                        'branch_id' => $data['branch_id'] ?? null,
                        'is_active' => $data['is_active'] ?? null,
                        'locale' => $data['locale'] ?? null,
                        'timezone' => $data['timezone'] ?? null,
                    ], fn ($value) => $value !== null);

                    if (! empty($data['password'])) {
                        $userData['password'] = Hash::make($data['password']);
                    }

                    $this->userRepository->update($user, $userData);

                    if (isset($data['roles'])) {
                        $this->userRepository->syncRoles($user, $data['roles']);
                    }

                    if (isset($data['branches'])) {
                        $this->userRepository->syncBranches($user, $data['branches']);
                    }

                    $this->logServiceInfo('updateUser', 'User updated successfully', ['user_id' => $user->id]);

                    return $user->fresh(['roles', 'branches', 'branch']);
                });
            },
            operation: 'updateUser',
            context: ['user_id' => $user->id]
        );
    }

    public function deleteUser(User $user): void
    {
        $this->handleServiceOperation(
            callback: function () use ($user) {
                $this->logServiceInfo('deleteUser', 'User deleted', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
                $this->userRepository->delete($user);
            },
            operation: 'deleteUser',
            context: ['user_id' => $user->id]
        );
    }

    public function changePassword(User $user, string $newPassword): User
    {
        return $this->handleServiceOperation(
            callback: function () use ($user, $newPassword) {
                $this->userRepository->update($user, [
                    'password' => Hash::make($newPassword),
                ]);

                $this->logServiceInfo('changePassword', 'User password changed', ['user_id' => $user->id]);

                return $user;
            },
            operation: 'changePassword',
            context: ['user_id' => $user->id]
        );
    }

    public function assignRoles(User $user, array $roles): User
    {
        return $this->handleServiceOperation(
            callback: fn () => $this->userRepository->syncRoles($user, $roles),
            operation: 'assignRoles',
            context: ['user_id' => $user->id, 'roles' => $roles]
        );
    }

    public function assignBranches(User $user, array $branchIds): User
    {
        return $this->handleServiceOperation(
            callback: fn () => $this->userRepository->syncBranches($user, $branchIds),
            operation: 'assignBranches',
            context: ['user_id' => $user->id, 'branch_count' => count($branchIds)]
        );
    }

    public function activateUser(User $user): User
    {
        return $this->handleServiceOperation(
            callback: function () use ($user) {
                $this->logServiceInfo('activateUser', 'User activated', ['user_id' => $user->id]);

                return $this->userRepository->activate($user);
            },
            operation: 'activateUser',
            context: ['user_id' => $user->id]
        );
    }

    public function deactivateUser(User $user): User
    {
        return $this->handleServiceOperation(
            callback: function () use ($user) {
                $this->logServiceInfo('deactivateUser', 'User deactivated', ['user_id' => $user->id]);

                return $this->userRepository->deactivate($user);
            },
            operation: 'deactivateUser',
            context: ['user_id' => $user->id]
        );
    }

    public function getUsersByBranch(int $branchId): Collection
    {
        return $this->handleServiceOperation(
            callback: fn () => $this->userRepository->getUsersByBranch($branchId),
            operation: 'getUsersByBranch',
            context: ['branch_id' => $branchId]
        );
    }

    public function searchUsers(string $query, int $limit = 10): Collection
    {
        return $this->handleServiceOperation(
            callback: fn () => $this->userRepository->paginateWithFilters([
                'search' => $query,
            ], $limit)->getCollection(),
            operation: 'searchUsers',
            context: ['query' => $query, 'limit' => $limit]
        );
    }
}
