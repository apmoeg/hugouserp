<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Provides friendly authorization methods that display user-friendly error messages
 * instead of hard abort(403) calls.
 *
 * This improves UX by showing clear, translated error messages to users
 * rather than generic 403 error pages.
 */
trait AuthorizesWithFriendlyErrors
{
    /**
     * Check if user has a permission, redirect with friendly error if not.
     */
    protected function requirePermission(string $permission, ?string $message = null): bool
    {
        $user = Auth::user();

        if (! $user) {
            session()->flash('error', __('You must be logged in to perform this action.'));
            $this->redirectRoute('login', navigate: true);

            return false;
        }

        if (! $user->can($permission)) {
            session()->flash('error', $message ?? __('You do not have permission to perform this action.'));

            return false;
        }

        return true;
    }

    /**
     * Check if user belongs to the same branch, redirect with friendly error if not.
     */
    protected function requireSameBranch(?int $branchId, ?string $message = null): bool
    {
        $user = Auth::user();

        if (! $user) {
            session()->flash('error', __('You must be logged in to perform this action.'));
            $this->redirectRoute('login', navigate: true);

            return false;
        }

        // Users with manage permission can access any branch
        if ($user->can('branches.view-all')) {
            return true;
        }

        if ($branchId && (int) $user->branch_id !== (int) $branchId) {
            session()->flash('error', $message ?? __('You cannot access resources from other branches.'));

            return false;
        }

        return true;
    }

    /**
     * Check if user can manage the module or is assigned to the resource.
     */
    protected function requireManageOrAssigned(string $managePermission, ?int $assignedTo, ?string $message = null): bool
    {
        $user = Auth::user();

        if (! $user) {
            session()->flash('error', __('You must be logged in to perform this action.'));
            $this->redirectRoute('login', navigate: true);

            return false;
        }

        // User has manage permission
        if ($user->can($managePermission)) {
            return true;
        }

        // User is assigned to this resource
        if ($assignedTo && (int) $user->id === (int) $assignedTo) {
            return true;
        }

        session()->flash('error', $message ?? __('You do not have permission to perform this action.'));

        return false;
    }

    /**
     * Show a friendly "no access" message and optionally redirect.
     */
    protected function showNoAccessError(?string $message = null, ?string $redirectRoute = null): void
    {
        session()->flash('error', $message ?? __('You do not have access to this resource.'));

        if ($redirectRoute) {
            $this->redirectRoute($redirectRoute, navigate: true);
        }
    }

    /**
     * Check multiple conditions and show friendly error if any fails.
     */
    protected function authorizeWithMessage(bool $condition, ?string $message = null): bool
    {
        if (! $condition) {
            session()->flash('error', $message ?? __('You do not have permission to perform this action.'));

            return false;
        }

        return true;
    }
}
