<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $per = min(max($request->integer('per_page', 20), 1), 100);

        $rows = Branch::query()
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->q.'%'))
            ->orderByDesc('is_main')
            ->orderBy('name')
            ->paginate($per);

        return $this->ok($rows);
    }

    public function store(Request $request)
    {
        $data = $this->validate($request, [
            'name' => ['required', 'string', 'max:255', 'unique:branches,name'],
            'code' => ['required', 'string', 'max:50', 'unique:branches,code'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $row = Branch::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return $this->ok($row, __('Created'), 201);
    }

    public function show(Branch $branch)
    {
        return $this->ok($branch);
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $this->validate($request, [
            'name' => ['sometimes', 'string', 'max:255', 'unique:branches,name,'.$branch->id],
            'code' => ['sometimes', 'string', 'max:50', 'unique:branches,code,'.$branch->id],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $branch->fill($data)->save();

        return $this->ok($branch, __('Updated'));
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();

        return $this->ok(null, __('Deleted'));
    }

    public function archive(Branch $branch)
    {
        $branch->is_active = false;
        $branch->save();

        return $this->ok($branch, __('Archived'));
    }
}
