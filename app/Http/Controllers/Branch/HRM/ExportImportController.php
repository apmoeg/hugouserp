<?php

namespace App\Http\Controllers\Branch\HRM;

use App\Http\Controllers\Controller;
use App\Traits\ExportsCsv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportImportController extends Controller
{
    use ExportsCsv;

    public function exportEmployees(Request $request): StreamedResponse
    {
        $query = \App\Models\HREmployee::query()->with(['branch', 'user']);
        
        $format = $request->input('format', 'xlsx');

        if ($format === 'xlsx') {
            return $this->exportToXlsx(
                $query,
                ['ID', 'Code', 'Name', 'Position', 'Salary', 'Active', 'Branch', 'User email'],
                fn ($row) => [
                    $row->id,
                    $row->code,
                    $row->name,
                    $row->position,
                    $row->salary,
                    $row->is_active ? 'Yes' : 'No',
                    $row->branch?->name ?? '',
                    $row->user?->email ?? '',
                ],
                'hrm_employees'
            );
        }

        return $this->exportToCsv(
            $query,
            ['ID', 'Code', 'Name', 'Position', 'Salary', 'Active', 'Branch', 'User email'],
            fn ($row) => [
                $row->id,
                $row->code,
                $row->name,
                $row->position,
                $row->salary,
                $row->is_active ? '1' : '0',
                $row->branch?->name ?? '',
                $row->user?->email ?? '',
            ],
            'hrm_employees'
        );
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
