<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch\HRM;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\Contracts\HRMServiceInterface as HRM;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(protected HRM $hrm) {}

    public function index()
    {
        $per = min(max(request()->integer('per_page', 20), 1), 100);

        return $this->ok(Attendance::query()->orderByDesc('logged_at')->paginate($per));
    }

    public function log(Request $request)
    {
        $data = $this->validate($request, [
            'employee_id' => ['required', 'exists:hr_employees,id'],
            'type' => ['required', 'in:in,out'],
            'at' => ['sometimes', 'date'],
        ]);

        return $this->ok($this->hrm->logAttendance($data['employee_id'], $data['type'], $request->input('at', now()->toDateTimeString())));
    }

    public function approve(Attendance $record)
    {
        return $this->ok($this->hrm->approveAttendance($record->id));
    }
}
