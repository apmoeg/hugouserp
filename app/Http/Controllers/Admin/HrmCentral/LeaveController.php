<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\HrmCentral;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $per = min(max($request->integer('per_page', 20), 1), 100);
        $q = LeaveRequest::query()->orderByDesc('created_at');
        if ($request->filled('employee_id')) {
            $q->where('employee_id', $request->integer('employee_id'));
        }
        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }

        return $this->ok($q->paginate($per));
    }

    public function updateStatus(Request $request, LeaveRequest $leave)
    {
        $data = $this->validate($request, ['status' => ['required', 'in:pending,approved,rejected']]);
        $leave->status = $data['status'];
        $leave->save();

        return $this->ok($leave, __('Status updated'));
    }
}
