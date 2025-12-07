<?php

namespace App\Http\Controllers\Branch\Rental;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function occupancy(Request $request): StreamedResponse
    {
        $model = '\\App\\Models\\RentalUnit';

        if (! class_exists($model)) {
            abort(500, 'RentalUnit model not found');
        }

        $query = $model::query()->with('property');

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->integer('property_id'));
        }

        $filename = 'rental_occupancy_'.now()->format('Ymd_His').'.csv';

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Property', 'Code', 'Type', 'Status', 'Rent', 'Deposit']);

            $query->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
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

    public function expiringContracts(Request $request): StreamedResponse
    {
        $days = $request->integer('days', 30);
        $threshold = now()->addDays($days)->toDateString();

        $model = '\\App\\Models\\RentalContract';

        if (! class_exists($model)) {
            abort(500, 'RentalContract model not found');
        }

        $query = $model::query()
            ->with(['unit.property', 'tenant'])
            ->where('status', 'active')
            ->whereDate('end_date', '<=', $threshold);

        $filename = 'rental_expiring_contracts_'.now()->format('Ymd_His').'.csv';

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Property', 'Unit', 'Tenant', 'Start date', 'End date', 'Rent', 'Deposit', 'Status']);

            $query->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
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
}
