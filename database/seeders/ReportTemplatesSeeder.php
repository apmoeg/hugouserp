<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ReportTemplate;
use Illuminate\Database\Seeder;

class ReportTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'pos_daily_summary',
                'name' => 'POS Daily Summary',
                'description' => 'Daily POS sales summary for a specific branch and date.',
                'route_name' => 'reports.pos.daily_summary',
                'default_filters' => [
                    'date' => now()->toDateString(),
                    'branch_id' => null,
                ],
                'output_type' => 'web',
                'export_columns' => null,
            ],
            [
                'key' => 'pos_margin_analysis',
                'name' => 'POS Margin Analysis',
                'description' => 'Margin analysis grouped by product or category.',
                'route_name' => 'reports.pos.margin_analysis',
                'default_filters' => [
                    'from' => now()->subDays(7)->toDateString(),
                    'to' => now()->toDateString(),
                    'branch_id' => null,
                ],
                'output_type' => 'excel',
                'export_columns' => [
                    'product',
                    'qty',
                    'net_total',
                    'cost_total',
                    'margin',
                ],
            ],
            [
                'key' => 'inventory_low_stock',
                'name' => 'Inventory Low Stock',
                'description' => 'Products that reached low-stock thresholds.',
                'route_name' => 'reports.inventory.low_stock',
                'default_filters' => [
                    'branch_id' => null,
                ],
                'output_type' => 'web',
                'export_columns' => [
                    'sku',
                    'name',
                    'current_stock',
                    'min_stock',
                ],
            ],
            [
                'key' => 'store_orders_by_source',
                'name' => 'Store Orders by Source',
                'description' => 'Store orders grouped by source (Shopify, WooCommerce, etc.).',
                'route_name' => 'admin.store.dashboard',
                'default_filters' => [
                    'from' => now()->subDays(30)->toDateString(),
                    'to' => now()->toDateString(),
                    'status' => null,
                    'source' => null,
                ],
                'output_type' => 'excel',
                'export_columns' => [
                    'external_order_id',
                    'source',
                    'status',
                    'total',
                    'discount_total',
                    'shipping_total',
                    'tax_total',
                    'created_at',
                ],
            ],
        ];

        foreach ($templates as $tpl) {
            ReportTemplate::query()->updateOrCreate(
                [
                    'key' => $tpl['key'],
                ],
                [
                    'name' => $tpl['name'],
                    'description' => $tpl['description'],
                    'route_name' => $tpl['route_name'],
                    'default_filters' => $tpl['default_filters'],
                    'output_type' => $tpl['output_type'],
                    'export_columns' => $tpl['export_columns'],
                    'is_active' => true,
                    'module' => $tpl['module'] ?? (str_starts_with($tpl['key'], 'pos_') ? 'pos'
                        : (str_starts_with($tpl['key'], 'inventory_') ? 'inventory'
                            : (str_starts_with($tpl['key'], 'store_') ? 'store' : 'general'))),
                    'required_permission' => $tpl['required_permission']
                        ?? (str_starts_with($tpl['key'], 'pos_') ? 'reports.pos.export'
                            : (str_starts_with($tpl['key'], 'inventory_') ? 'reports.inventory.export'
                                : (str_starts_with($tpl['key'], 'store_') ? 'store.reports.dashboard' : null))),
                ]
            );
        }
    }
}
