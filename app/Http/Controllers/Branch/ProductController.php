<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductImageRequest;
use App\Http\Requests\ProductImportRequest;
use App\Models\Product;
use App\Services\Contracts\ProductServiceInterface as Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function __construct(protected Products $products) {}

    public function search(Request $request)
    {
        $q = (string) $request->query('q', '');
        $perPage = (int) $request->query('per_page', 15);

        $results = $this->products->search($q, $perPage);

        return $this->ok($results);
    }

    public function import(ProductImportRequest $request)
    {
        $file = $request->file('file');
        $path = $file->store('imports', 'local');

        $count = $this->products->importCsv('local', $path);

        return $this->ok(['imported' => $count], __('Imported'));
    }

    public function export()
    {
        $path = $this->products->exportCsv('local', 'exports/products.csv');

        return $this->ok([
            'path' => $path,
            'url' => Storage::disk('local')->url($path),
        ], __('Export generated'));
    }

    public function uploadImage(ProductImageRequest $request, Product $product)
    {
        $this->authorize('products.manage');

        $path = $request->file('image')->store('product-images', 'public');
        $product->image_path = $path;
        $product->save();

        return $this->ok(['path' => $path]);
    }

    public function destroy(Product $product)
    {
        $this->authorize('products.delete');

        $product->delete();

        return $this->ok(null, __('Product deleted successfully'));
    }
}
