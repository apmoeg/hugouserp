<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use App\Models\Product;
use App\Models\ProductStoreMapping;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class ProductStoreMappings extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public ?int $productId = null;

    public ?Product $product = null;

    public string $search = '';

    public ?int $storeFilter = null;

    public array $stores = [];

    public function mount(?int $productId = null): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('inventory.products.view')) {
            abort(403);
        }

        $this->productId = $productId;

        if ($productId) {
            $this->product = Product::findOrFail($productId);

            // Enforce branch scoping: user must have access to product's branch
            $this->authorizeProductBranch($this->product);
        }

        $this->loadStores();
    }

    /**
     * Verify the user has a valid branch assignment.
     */
    protected function requireUserBranch(): int
    {
        $user = Auth::user();

        if (! $user || ! $user->branch_id) {
            abort(403, __('User must be assigned to a branch to perform this action'));
        }

        return $user->branch_id;
    }

    /**
     * Verify the product belongs to the user's branch.
     */
    protected function authorizeProductBranch(Product $product): void
    {
        $userBranchId = $this->requireUserBranch();

        if ($product->branch_id !== $userBranchId) {
            abort(403, __('Access denied to product from another branch'));
        }
    }

    /**
     * Check if the user has the required permission.
     */
    protected function authorizeAction(string $permission): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can($permission)) {
            abort(403, __('Unauthorized'));
        }
    }

    protected function loadStores(): void
    {
        $query = Store::where('is_active', true);

        if ($this->product && $this->product->branch_id) {
            $query->where(function ($q) {
                $q->where('branch_id', $this->product->branch_id)
                    ->orWhereNull('branch_id');
            });
        }

        $this->stores = $query->orderBy('name')->get(['id', 'name', 'type'])->toArray();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $this->authorizeAction('inventory.products.delete');

        $mapping = ProductStoreMapping::with('product')->findOrFail($id);

        // Verify branch ownership before delete
        if ($mapping->product) {
            $this->authorizeProductBranch($mapping->product);
        }

        $mapping->delete();
        session()->flash('success', __('Mapping deleted successfully'));
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $query = ProductStoreMapping::with('store');

        if ($this->productId) {
            $query->where('product_id', $this->productId);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('external_id', 'like', '%'.$this->search.'%')
                    ->orWhere('external_sku', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->storeFilter) {
            $query->where('store_id', $this->storeFilter);
        }

        $mappings = $query->orderByDesc('created_at')->paginate(15);

        return view('livewire.inventory.product-store-mappings', [
            'mappings' => $mappings,
        ]);
    }
}
