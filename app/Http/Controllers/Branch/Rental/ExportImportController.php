<?php

namespace App\Http\Controllers\Branch\Rental;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportImportController extends Controller
{
    public function exportUnits(Request $request): StreamedResponse
    {
        $model = '\\App\\Models\\RentalUnit';

        if (! class_exists($model)) {
            abort(500, 'RentalUnit model not found');
        }

        $query = $model::query()->with('property');

        $filename = 'rental_units_'.now()->format('Ymd_His').'.csv';

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Property', 'Code', 'Type', 'Status', 'Rent', 'Deposit']);

            $query->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        optional($row->property)->name ?? '',
                        $row->code,
                        $row->type,
                        $row->status,
                        $row->rent,
                        $row->deposit,
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportTenants(Request $request): StreamedResponse
    {
        $model = '\\App\\Models\\Tenant';

        if (! class_exists($model)) {
            abort(500, 'Tenant model not found');
        }

        $query = $model::query();

        $filename = 'rental_tenants_'.now()->format('Ymd_His').'.csv';

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Name', 'Email', 'Phone', 'Address', 'Active']);

            $query->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->name,
                        $row->email,
                        $row->phone,
                        $row->address,
                        $row->is_active ? '1' : '0',
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportContracts(Request $request): StreamedResponse
    {
        $model = '\\App\\Models\\RentalContract';

        if (! class_exists($model)) {
            abort(500, 'RentalContract model not found');
        }

        $query = $model::query()->with(['unit.property', 'tenant']);

        $filename = 'rental_contracts_'.now()->format('Ymd_His').'.csv';

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Property', 'Unit', 'Tenant', 'Start date', 'End date', 'Rent', 'Deposit', 'Status']);

            $query->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        optional(optional($row->unit)->property)->name ?? '',
                        optional($row->unit)->code ?? '',
                        optional($row->tenant)->name ?? '',
                        $row->start_date,
                        $row->end_date,
                        $row->rent,
                        $row->deposit,
                        $row->status,
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function importUnits(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        fgetcsv($handle); // skip header

        $model = '\\App\\Models\\RentalUnit';

        if (! class_exists($model)) {
            abort(500, 'RentalUnit model not found');
        }

        DB::transaction(function () use ($handle, $model) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if (count($data) < 7) {
                    continue;
                }

                [$id, $propertyName, $code, $type, $status, $rent, $deposit] = $data;

                $model::updateOrCreate(
                    ['id' => $id],
                    [
                        'code' => $code,
                        'type' => $type,
                        'status' => $status,
                        'rent' => $rent,
                        'deposit' => $deposit,
                    ]
                );
            }
        });

        fclose($handle);

        return back()->with('status', 'Units imported successfully');
    }

    public function importTenants(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        fgetcsv($handle); // header

        $model = '\\App\\Models\\Tenant';

        if (! class_exists($model)) {
            abort(500, 'Tenant model not found');
        }

        DB::transaction(function () use ($handle, $model) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if (count($data) < 6) {
                    continue;
                }

                [$id, $name, $email, $phone, $address, $active] = $data;

                $model::updateOrCreate(
                    ['id' => $id],
                    [
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'address' => $address,
                        'is_active' => (bool) $active,
                    ]
                );
            }
        });

        fclose($handle);

        return back()->with('status', 'Tenants imported successfully');
    }
}
