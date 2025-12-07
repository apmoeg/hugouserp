<?php

namespace App\Http\Controllers\Branch\HRM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportImportController extends Controller
{
    public function exportEmployees(Request $request): StreamedResponse
    {
        $model = '\\App\\Models\\HREmployee';

        if (! class_exists($model)) {
            abort(500, 'HREmployee model not found');
        }

        $query = $model::query()->with(['branch', 'user']);

        $filename = 'hrm_employees_'.now()->format('Ymd_His').'.csv';

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Code', 'Name', 'Position', 'Salary', 'Active', 'Branch', 'User email']);

            $query->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->code,
                        $row->name,
                        $row->position,
                        $row->salary,
                        $row->is_active ? '1' : '0',
                        optional($row->branch)->name ?? '',
                        optional($row->user)->email ?? '',
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function importEmployees(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');

        // Skip header
        fgetcsv($handle);

        $model = '\\App\\Models\\HREmployee';

        if (! class_exists($model)) {
            abort(500, 'HREmployee model not found');
        }

        DB::transaction(function () use ($handle, $model) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if (count($data) < 6) {
                    continue;
                }

                [$id, $code, $name, $position, $salary, $active] = $data;

                $model::updateOrCreate(
                    ['id' => $id],
                    [
                        'code' => $code,
                        'name' => $name,
                        'position' => $position,
                        'salary' => $salary,
                        'is_active' => (bool) $active,
                    ]
                );
            }
        });

        fclose($handle);

        return back()->with('status', 'Employees imported successfully');
    }
}
