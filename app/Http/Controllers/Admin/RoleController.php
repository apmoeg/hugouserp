<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $per = min(max($request->integer('per_page', 50), 1), 200);
        $q = Role::query()->orderBy('name');
        if ($s = $request->input('q')) {
            $q->where('name', 'like', '%'.$s.'%');
        }

        return $this->ok($q->paginate($per));
    }

    public function store(Request $request)
    {
        $data = $this->validate($request, ['name' => ['required', 'string', 'max:190', 'unique:roles,name']]);

        return $this->ok(Role::create($data), __('Created'), 201);
    }

    public function update(Request $request, int $id)
    {
        $role = Role::findOrFail($id);
        $data = $this->validate($request, ['name' => ['required', 'string', 'max:190', 'unique:roles,name,'.$id]]);
        $role->fill($data)->save();

        return $this->ok($role, __('Updated'));
    }

    public function destroy(int $id)
    {
        Role::query()->whereKey($id)->delete();

        return $this->ok(null, __('Deleted'));
    }
}
