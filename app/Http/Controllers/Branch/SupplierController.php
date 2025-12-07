<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierStoreRequest;
use App\Http\Requests\SupplierUpdateRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $per = min(max($request->integer('per_page', 20), 1), 100);
        $rows = Supplier::query()
            ->when($request->filled('q'), fn ($q) => $q->where(function ($qq) use ($request) {
                $qq->where('name', 'like', '%'.$request->q.'%')->orWhere('phone', 'like', '%'.$request->q.'%');
            }))
            ->where('branch_id', (int) $request->attributes->get('branch_id'))
            ->orderByDesc('id')->paginate($per);

        return $this->ok($rows);
    }

    public function store(SupplierStoreRequest $request)
    {
        $data = $request->validated();
        $row = Supplier::create($data + ['branch_id' => (int) $request->attributes->get('branch_id')]);

        return $this->ok($row, __('Created'), 201);
    }

    public function show(Supplier $supplier)
    {
        return $this->ok($supplier);
    }

    public function update(SupplierUpdateRequest $request, Supplier $supplier)
    {
        $supplier->fill($request->validated())->save();

        return $this->ok($supplier, __('Updated'));
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return $this->ok(null, __('Deleted'));
    }
}
