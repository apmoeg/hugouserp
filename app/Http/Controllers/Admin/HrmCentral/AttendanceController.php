<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\HrmCentral;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $per = min(max($request->integer('per_page', 20), 1), 100);
        $q = Attendance::query()->orderByDesc('logged_at');
        if ($request->filled('employee_id')) {
            $q->where('employee_id', $request->integer('employee_id'));
        }
        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }

        return $this->ok($q->paginate($per));
    }

    public function approve(Attendance $record)
    {
        $record->status = 'approved';
        $record->approved_at = now();
        $record->save();

        return $this->ok($record, __('Approved'));
    }
}
