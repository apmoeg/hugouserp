<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\HrmCentral;

use App\Http\Controllers\Controller;
use App\Models\HREmployee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $per = min(max($request->integer('per_page', 20), 1), 100);
        $rows = HREmployee::query()
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->q.'%'))
            ->orderByDesc('id')->paginate($per);

        return $this->ok($rows);
    }

    public function show(HREmployee $employee)
    {
        return $this->ok($employee);
    }

    public function update(Request $request, HREmployee $employee)
    {
        $data = $this->validate($request, [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:hr_employees,email,'.$employee->id],
            'salary' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);
        $employee->fill($data)->save();

        return $this->ok($employee, __('Updated'));
    }
}
