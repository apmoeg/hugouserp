<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerStoreRequest;
use App\Http\Requests\CustomerUpdateRequest;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $per = min(max($request->integer('per_page', 20), 1), 100);
        $rows = Customer::query()
            ->when($request->filled('q'), fn ($q) => $q->where(function ($qq) use ($request) {
                $qq->where('name', 'like', '%'.$request->q.'%')->orWhere('phone', 'like', '%'.$request->q.'%');
            }))
            ->where('branch_id', (int) $request->attributes->get('branch_id'))
            ->orderByDesc('id')->paginate($per);

        return $this->ok($rows);
    }

    public function store(CustomerStoreRequest $request)
    {
        $data = $request->validated();
        $row = Customer::create($data + ['branch_id' => (int) $request->attributes->get('branch_id')]);

        return $this->ok($row, __('Created'), 201);
    }

    public function show(Customer $customer)
    {
        return $this->ok($customer);
    }

    public function update(CustomerUpdateRequest $request, Customer $customer)
    {
        $customer->fill($request->validated())->save();

        return $this->ok($customer, __('Updated'));
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return $this->ok(null, __('Deleted'));
    }
}
