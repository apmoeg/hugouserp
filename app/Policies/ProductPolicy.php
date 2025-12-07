<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ProductPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'products.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $this->has($user, 'products.view');
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'products.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $this->has($user, 'products.update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->has($user, 'products.delete');
    }

    public function restore(User $user, Product $product): bool
    {
        return $this->has($user, 'products.update');
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return $this->has($user, 'products.delete');
    }

    public function import(User $user): bool
    {
        return $this->has($user, 'products.import');
    }

    public function export(User $user): bool
    {
        return $this->has($user, 'products.export');
    }

    public function uploadImage(User $user, Product $product): bool
    {
        return $this->has($user, 'products.image.upload');
    }
}
