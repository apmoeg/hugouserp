<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Services\Contracts\ModuleFieldServiceInterface;
use App\Services\Contracts\ProductServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService implements ProductServiceInterface
{
    use HandlesServiceErrors;

    public function __construct(
        protected ModuleFieldServiceInterface $moduleFields,
    ) {}

    /** @return \Illuminate\Contracts\Pagination\LengthAwarePaginator */
    public function search(string $q = '', int $perPage = 15)
    {
        return $this->handleServiceOperation(
            callback: function () use ($q, $perPage) {
                $query = Product::query();

                if ($q !== '') {
                    $like = '%'.$q.'%';
                    $query->where(function ($inner) use ($like) {
                        $inner->where('name', 'like', $like)
                            ->orWhere('sku', 'like', $like)
                            ->orWhere('barcode', 'like', $like);
                    });
                }

                return $query->orderBy('name')->paginate($perPage);
            },
            operation: 'search',
            context: ['query' => $q, 'per_page' => $perPage]
        );
    }

    public function importCsv(string $disk, string $path): int
    {
        return $this->handleServiceOperation(
            callback: function () use ($disk, $path) {
                if (! Storage::disk($disk)->exists($path)) {
                    return 0;
                }

                $stream = Storage::disk($disk)->readStream($path);
                if (! $stream) {
                    return 0;
                }

                $header = fgetcsv($stream);
                if (! $header) {
                    fclose($stream);

                    return 0;
                }

                $normalized = [];
                foreach ($header as $index => $column) {
                    $key = strtolower(trim((string) $column));
                    if ($key === '') {
                        continue;
                    }
                    $normalized[$key] = $index;
                }

                $baseColumns = ['sku', 'name', 'price', 'cost', 'barcode'];

                $exportableDynamic = $this->moduleFields->exportColumns('inventory', 'products', null);
                $exportableDynamicLower = array_map('strtolower', $exportableDynamic);

                $dynamicColumns = [];
                foreach ($normalized as $key => $index) {
                    if (in_array($key, $baseColumns, true)) {
                        continue;
                    }
                    if (in_array($key, $exportableDynamicLower, true)) {
                        $dynamicColumns[$key] = $index;
                    }
                }

                $imported = 0;

                DB::beginTransaction();

                try {
                    while (($row = fgetcsv($stream)) !== false) {
                        if (! array_filter($row, fn ($value) => $value !== null && $value !== '')) {
                            continue;
                        }

                        $sku = $this->valueOrNull($row, $normalized, 'sku');
                        $name = $this->valueOrNull($row, $normalized, 'name');

                        if ($sku === null && $name === null) {
                            continue;
                        }

                        /** @var Product $product */
                        $product = $sku
                            ? Product::query()->where('sku', $sku)->first() ?? new Product
                            : new Product;

                        if ($sku !== null) {
                            $product->sku = $sku;
                        }

                        if ($name !== null) {
                            $product->name = $name;
                        }

                        $price = $this->valueOrNull($row, $normalized, 'price');
                        if ($price !== null) {
                            $product->price = (float) $price;
                        }

                        $cost = $this->valueOrNull($row, $normalized, 'cost');
                        if ($cost !== null) {
                            $product->cost = (float) $cost;
                        }

                        $barcode = $this->valueOrNull($row, $normalized, 'barcode');
                        if ($barcode !== null) {
                            $product->barcode = $barcode;
                        }

                        $attrs = (array) ($product->extra_attributes ?? []);
                        foreach ($dynamicColumns as $key => $index) {
                            $attrs[$key] = $row[$index] ?? null;
                        }
                        $product->extra_attributes = $attrs;

                        $product->save();
                        $imported++;
                    }

                    fclose($stream);
                    DB::commit();

                    $this->logServiceInfo('importCsv', 'CSV import completed', [
                        'disk' => $disk,
                        'path' => $path,
                        'imported_count' => $imported,
                    ]);
                } catch (\Throwable $e) {
                    fclose($stream);
                    DB::rollBack();
                    throw $e;
                }

                return $imported;
            },
            operation: 'importCsv',
            context: ['disk' => $disk, 'path' => $path],
            defaultValue: 0
        );
    }

    public function exportCsv(string $disk, string $path): string
    {
        return $this->handleServiceOperation(
            callback: function () use ($disk, $path) {
                $dynamicKeys = $this->moduleFields->exportColumns('inventory', 'products', null);
                $dynamicKeys = array_values(array_unique($dynamicKeys));

                $fh = fopen('php://temp', 'w+');

                $header = ['sku', 'name', 'price', 'cost', 'barcode'];
                foreach ($dynamicKeys as $key) {
                    $header[] = $key;
                }
                fputcsv($fh, $header);

                Product::query()
                    ->orderBy('id')
                    ->chunk(500, function ($chunk) use ($fh, $dynamicKeys) {
                        foreach ($chunk as $p) {
                            $row = [
                                $p->sku,
                                $p->name,
                                $p->price,
                                $p->cost,
                                $p->barcode,
                            ];

                            $attrs = (array) ($p->extra_attributes ?? []);
                            foreach ($dynamicKeys as $key) {
                                $row[] = $attrs[$key] ?? null;
                            }

                            fputcsv($fh, $row);
                        }
                    });

                rewind($fh);
                $content = stream_get_contents($fh);
                fclose($fh);

                Storage::disk($disk)->put($path, $content);

                $this->logServiceInfo('exportCsv', 'CSV export completed', [
                    'disk' => $disk,
                    'path' => $path,
                ]);

                return $path;
            },
            operation: 'exportCsv',
            context: ['disk' => $disk, 'path' => $path]
        );
    }

    private function valueOrNull(array $row, array $normalized, string $key): ?string
    {
        if (! isset($normalized[$key])) {
            return null;
        }

        $value = $row[$normalized[$key]] ?? null;

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
