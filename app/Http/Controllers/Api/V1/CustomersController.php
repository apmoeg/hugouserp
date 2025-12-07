<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomersController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $store = $this->getStore($request);

        $query = Customer::query()
            ->when($store?->branch_id, fn ($q) => $q->where('branch_id', $store->branch_id))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('email', 'like', '%'.$request->search.'%')
                ->orWhere('phone', 'like', '%'.$request->search.'%')
            )
            ->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_dir', 'desc'));

        $customers = $query->paginate($request->get('per_page', 50));

        return $this->paginatedResponse($customers, __('Customers retrieved successfully'));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $store = $this->getStore($request);

        $customer = Customer::query()
            ->when($store?->branch_id, fn ($q) => $q->where('branch_id', $store->branch_id))
            ->find($id);

        if (! $customer) {
            return $this->errorResponse(__('Customer not found'), 404);
        }

        $customer->load(['sales' => fn ($q) => $q->latest()->take(10)]);

        return $this->successResponse($customer, __('Customer retrieved successfully'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:customers,email',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'company' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'external_id' => 'nullable|string|max:100',
        ]);

        $store = $this->getStore($request);
        $validated['branch_id'] = $store?->branch_id;

        $customer = Customer::create($validated);

        return $this->successResponse($customer, __('Customer created successfully'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $store = $this->getStore($request);

        $customer = Customer::query()
            ->when($store?->branch_id, fn ($q) => $q->where('branch_id', $store->branch_id))
            ->find($id);

        if (! $customer) {
            return $this->errorResponse(__('Customer not found'), 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255|unique:customers,email,'.$customer->id,
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'company' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $customer->update($validated);

        return $this->successResponse($customer, __('Customer updated successfully'));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $store = $this->getStore($request);

        $customer = Customer::query()
            ->when($store?->branch_id, fn ($q) => $q->where('branch_id', $store->branch_id))
            ->find($id);

        if (! $customer) {
            return $this->errorResponse(__('Customer not found'), 404);
        }

        $customer->delete();

        return $this->successResponse(null, __('Customer deleted successfully'));
    }

    public function byEmail(Request $request, string $email): JsonResponse
    {
        $store = $this->getStore($request);

        $customer = Customer::query()
            ->when($store?->branch_id, fn ($q) => $q->where('branch_id', $store->branch_id))
            ->where('email', $email)
            ->first();

        if (! $customer) {
            return $this->errorResponse(__('Customer not found'), 404);
        }

        return $this->successResponse($customer, __('Customer retrieved successfully'));
    }
}
