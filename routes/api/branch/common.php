<?php

use App\Http\Controllers\Branch\CustomerController;
use App\Http\Controllers\Branch\PosController;
use App\Http\Controllers\Branch\ProductController;
use App\Http\Controllers\Branch\PurchaseController;
use App\Http\Controllers\Branch\ReportsController as BranchReportsController;
use App\Http\Controllers\Branch\SaleController;
use App\Http\Controllers\Branch\StockController;
use App\Http\Controllers\Branch\SupplierController;
use App\Http\Controllers\Branch\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|------------------------------------------------------------------------------
| Branch common routes
| NOTE: parent group (/api/v1/branches/{branch}) already applies:
|   - api-core
|   - api-auth
|   - api-branch
|------------------------------------------------------------------------------
*/

// Warehouses
Route::prefix('warehouses')->group(function () {
    Route::get('/', [WarehouseController::class, 'index'])->middleware('perm:warehouses.view');
    Route::post('/', [WarehouseController::class, 'store'])->middleware('perm:warehouses.create');
    Route::get('{warehouse}', [WarehouseController::class, 'show'])->middleware('perm:warehouses.view');
    Route::match(['put', 'patch'], '{warehouse}', [WarehouseController::class, 'update'])->middleware('perm:warehouses.update');
    Route::delete('{warehouse}', [WarehouseController::class, 'destroy'])->middleware('perm:warehouses.delete');
});

// Suppliers
Route::prefix('suppliers')->group(function () {
    Route::get('/', [SupplierController::class, 'index'])->middleware('perm:suppliers.view');
    Route::post('/', [SupplierController::class, 'store'])->middleware('perm:suppliers.create');
    Route::get('{supplier}', [SupplierController::class, 'show'])->middleware('perm:suppliers.view');
    Route::match(['put', 'patch'], '{supplier}', [SupplierController::class, 'update'])->middleware('perm:suppliers.update');
    Route::delete('{supplier}', [SupplierController::class, 'destroy'])->middleware('perm:suppliers.delete');
});

// Customers
Route::prefix('customers')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->middleware('perm:customers.view');
    Route::post('/', [CustomerController::class, 'store'])->middleware('perm:customers.create');
    Route::get('{customer}', [CustomerController::class, 'show'])->middleware('perm:customers.view');
    Route::match(['put', 'patch'], '{customer}', [CustomerController::class, 'update'])->middleware('perm:customers.update');
    Route::delete('{customer}', [CustomerController::class, 'destroy'])->middleware('perm:customers.delete');
});

// Products
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->middleware('perm:products.view');
    Route::post('/', [ProductController::class, 'store'])->middleware('perm:products.create');
    Route::get('search', [ProductController::class, 'search'])->middleware('perm:products.view');
    Route::post('import', [ProductController::class, 'import'])->middleware('perm:products.import');
    Route::get('export', [ProductController::class, 'export'])->middleware('perm:products.export');
    Route::get('{product}', [ProductController::class, 'show'])->middleware('perm:products.view');
    Route::match(['put', 'patch'], '{product}', [ProductController::class, 'update'])->middleware('perm:products.update');
    Route::delete('{product}', [ProductController::class, 'destroy'])->middleware('perm:products.delete');
    Route::post('{product}/image', [ProductController::class, 'uploadImage'])->middleware('perm:products.image.upload');
});

// Stock
Route::prefix('stock')->group(function () {
    Route::get('current', [StockController::class, 'current'])->middleware('perm:stock.view');
    Route::post('adjust', [StockController::class, 'adjust'])->middleware('perm:stock.adjust');
    Route::post('transfer', [StockController::class, 'transfer'])->middleware('perm:stock.transfer');
});

// Purchases
Route::prefix('purchases')->group(function () {
    Route::get('/', [PurchaseController::class, 'index'])->middleware('perm:purchases.view');
    Route::post('/', [PurchaseController::class, 'store'])->middleware('perm:purchases.create');
    Route::get('{purchase}', [PurchaseController::class, 'show'])->middleware('perm:purchases.view');
    Route::match(['put', 'patch'], '{purchase}', [PurchaseController::class, 'update'])->middleware('perm:purchases.update');
    Route::post('{purchase}/approve', [PurchaseController::class, 'approve'])->middleware('perm:purchases.approve');
    Route::post('{purchase}/receive', [PurchaseController::class, 'receive'])->middleware('perm:purchases.receive');
    Route::post('{purchase}/pay', [PurchaseController::class, 'pay'])->middleware('perm:purchases.pay');
    Route::post('{purchase}/return', [PurchaseController::class, 'handleReturn'])->middleware('perm:purchases.return');
    Route::post('{purchase}/cancel', [PurchaseController::class, 'cancel'])->middleware('perm:purchases.cancel');
});

// Sales
Route::prefix('sales')->group(function () {
    Route::get('/', [SaleController::class, 'index'])->middleware('perm:sales.view');
    Route::post('/', [SaleController::class, 'store'])->middleware('perm:sales.create');
    Route::get('{sale}', [SaleController::class, 'show'])->middleware('perm:sales.view');
    Route::match(['put', 'patch'], '{sale}', [SaleController::class, 'update'])->middleware('perm:sales.update');
    Route::post('{sale}/return', [SaleController::class, 'handleReturn'])->middleware('perm:sales.return');
    Route::post('{sale}/void', [SaleController::class, 'voidSale'])->middleware('perm:sales.void');
    Route::post('{sale}/print', [SaleController::class, 'printInvoice'])->middleware('perm:sales.print');
});

// POS (حساس) — نستخدم ستاك موحّد للحماية بدل تكرار الميدلوير داخل كل مسار
Route::prefix('pos')->middleware(['pos-protected'])->group(function () {
    Route::post('checkout', [PosController::class, 'checkout']);
    Route::post('hold', [PosController::class, 'hold']);
    Route::post('resume', [PosController::class, 'resume']);
    Route::post('close-day', [PosController::class, 'closeDay']);
    Route::post('reprint/{sale}', [PosController::class, 'reprint']);
    Route::get('x-report', [PosController::class, 'xReport']);
    Route::post('z-report', [PosController::class, 'zReport']);
});

// Branch Reports
Route::prefix('reports')->group(function () {
    Route::get('branch-summary', [BranchReportsController::class, 'branchSummary'])->middleware('perm:reports.branch');
    Route::get('module-summary', [BranchReportsController::class, 'moduleSummary'])->middleware('perm:reports.branch');
    Route::get('top-products', [BranchReportsController::class, 'topProducts'])->middleware('perm:reports.branch');
    Route::get('stock-aging', [BranchReportsController::class, 'stockAging'])->middleware('perm:reports.branch');
    Route::get('pnl', [BranchReportsController::class, 'pnl'])->middleware('perm:reports.branch');
    Route::get('cashflow', [BranchReportsController::class, 'cashflow'])->middleware('perm:reports.branch');
});
