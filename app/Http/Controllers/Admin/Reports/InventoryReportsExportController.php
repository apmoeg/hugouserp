<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryReportsExportController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        if (! $user || ! $user->can('reports.inventory.export')) {
            abort(403);
        }

        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer'],
            'only_low' => ['nullable', 'boolean'],
            'format' => ['nullable', 'in:web,excel,pdf'],
        ]);

        $format = $validated['format'] ?? 'web';

        $query = Product::query();

        if (! empty($validated['branch_id'])) {
            $query->where('default_branch_id', $validated['branch_id']);
        }

        $products = $query->orderBy('name')->limit(5000)->get();

        $columns = [
            'id' => 'ID',
            'sku' => 'SKU',
            'name' => 'Name',
            'stock_qty' => 'Stock',
            'reorder_level' => 'Reorder Level',
        ];

        $rows = $products->map(function (Product $product) use ($validated) {
            $stock = $product->stock_qty ?? 0;
            $reorder = $product->reorder_level ?? 0;

            if (! empty($validated['only_low']) && $reorder > 0 && $stock > $reorder) {
                return null;
            }

            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'stock_qty' => $stock,
                'reorder_level' => $reorder,
            ];
        })->filter()->values()->toArray();

        if ($format === 'excel') {
            $response = new StreamedResponse(function () use ($columns, $rows): void {
                $handle = fopen('php://output', 'wb');

                fputcsv($handle, array_values($columns));

                foreach ($rows as $row) {
                    fputcsv($handle, array_map(fn ($v) => is_scalar($v) ? $v : json_encode($v), $row));
                }

                fclose($handle);
            });

            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="inventory_report_'.now()->format('Ymd_His').'.csv"');

            return $response;
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.reports.inventory-export-pdf', [
                'columns' => $columns,
                'rows' => $rows,
            ]);

            return $pdf->download('inventory_report_'.now()->format('Ymd_His').'.pdf');
        }

        return view('admin.reports.inventory-export-web', [
            'columns' => $columns,
            'rows' => $rows,
        ]);
    }
}
