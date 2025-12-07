<?php

namespace App\Http\Controllers\Branch\HRM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function attendance(Request $request): StreamedResponse
    {
        $model = '\\App\\Models\\Attendance';

        if (! class_exists($model)) {
            abort(500, 'Attendance model not found');
        }

        /** @var \App\Models\Attendance $model */
        $query = $model::query()->with('employee');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->get('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->get('to'));
        }

        $filename = 'hrm_attendance_'.now()->format('Ymd_His').'.csv';

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Employee', 'Date', 'Check in', 'Check out', 'Status', 'Approved at']);

            $query->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        optional($row->employee)->name ?? '',
                        $row->date,
                        $row->check_in,
                        $row->check_out,
                        $row->status,
                        $row->approved_at,
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function payroll(Request $request): StreamedResponse
    {
        $model = '\\App\\Models\\Payroll';

        if (! class_exists($model)) {
            abort(500, 'Payroll model not found');
        }

        /** @var \App\Models\Payroll $model */
        $query = $model::query()->with('employee');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        if ($request->filled('period')) {
            $query->where('period', $request->get('period'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $filename = 'hrm_payroll_'.now()->format('Ymd_His').'.csv';

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Employee', 'Period', 'Basic', 'Allowances', 'Deductions', 'Net', 'Status', 'Paid at']);

            $query->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        optional($row->employee)->name ?? '',
                        $row->period,
                        $row->basic,
                        $row->allowances,
                        $row->deductions,
                        $row->net,
                        $row->status,
                        $row->paid_at,
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
