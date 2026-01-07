<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\Contracts\PurchaseServiceInterface;
use App\Traits\HandlesServiceErrors;
use App\Traits\HasRequestContext;
use Illuminate\Support\Facades\DB;

class PurchaseService implements PurchaseServiceInterface
{
    use HandlesServiceErrors;
    use HasRequestContext;

    protected function branchIdOrFail(): int
    {
        $branchId = $this->currentBranchId();

        if ($branchId === null) {
            throw new \InvalidArgumentException('Branch context is required for purchase operations.');
        }

        return $branchId;
    }

    protected function findBranchPurchaseOrFail(int $id): Purchase
    {
        $branchId = $this->branchIdOrFail();

        return Purchase::where('branch_id', $branchId)->findOrFail($id);
    }

    public function create(array $payload): Purchase
    {
        return $this->handleServiceOperation(
            callback: fn () => DB::transaction(function () use ($payload) {
                // Controller provides branch_id in payload after validation
                // Service validates it exists as defense-in-depth
                if (! isset($payload['branch_id'])) {
                    $branchId = $this->branchIdOrFail();
                } else {
                    $branchId = (int) $payload['branch_id'];
                }

                $p = Purchase::create([
                    'branch_id' => $branchId,
                    'warehouse_id' => $payload['warehouse_id'] ?? null,
                    'supplier_id' => $payload['supplier_id'] ?? null,
                    'status' => 'draft',
                    'purchase_date' => now()->toDateString(),
                    // Use correct migration column names
                    'subtotal' => 0, 'tax_amount' => 0, 'discount_amount' => 0, 'total_amount' => 0,
                    'paid_amount' => 0,
                ]);

                $subtotal = '0';

                foreach ($payload['items'] ?? [] as $it) {
                    // Skip invalid items without required fields
                    if (! isset($it['product_id']) || ! isset($it['qty'])) {
                        continue;
                    }

                    $qty = (float) $it['qty'];
                    // Accept price from multiple possible field names for API compatibility
                    $unitPrice = (float) ($it['unit_price'] ?? $it['price'] ?? 0);

                    // Critical ERP: Validate positive quantities and prices
                    if ($qty <= 0) {
                        throw new \InvalidArgumentException("Quantity must be positive for product {$it['product_id']}");
                    }

                    if ($unitPrice < 0) {
                        throw new \InvalidArgumentException("Unit price cannot be negative for product {$it['product_id']}");
                    }

                    // Use bcmath for precise calculation
                    $lineTotal = bcmul((string) $qty, (string) $unitPrice, 2);
                    $subtotal = bcadd($subtotal, $lineTotal, 2);

                    // Get product name and SKU
                    $product = \App\Models\Product::find($it['product_id']);

                    PurchaseItem::create([
                        'purchase_id' => $p->getKey(),
                        'product_id' => $it['product_id'],
                        'product_name' => $product?->name ?? '',
                        'sku' => $product?->sku ?? null,
                        'quantity' => $qty,
                        'received_quantity' => 0,
                        'unit_price' => $unitPrice,
                        'discount_percent' => (float) ($it['discount_percent'] ?? 0),
                        'tax_percent' => (float) ($it['tax_percent'] ?? 0),
                        'tax_amount' => (float) ($it['tax_amount'] ?? 0),
                        'line_total' => (float) $lineTotal,
                    ]);
                }

                // Use correct migration column names
                $p->subtotal = (float) $subtotal;
                $p->total_amount = $p->subtotal;

                // Critical ERP: Validate supplier minimum order value
                if ($p->supplier_id) {
                    $supplier = \App\Models\Supplier::find($p->supplier_id);
                    if ($supplier && $supplier->minimum_order_amount > 0) {
                        if ($p->total_amount < $supplier->minimum_order_amount) {
                            throw new \InvalidArgumentException(
                                "Order total ({$p->total_amount}) is below supplier minimum order value ({$supplier->minimum_order_amount})"
                            );
                        }
                    }
                }

                $p->save();

                return $p;
            }),
            operation: 'create',
            context: ['payload' => $payload]
        );
    }

    public function approve(int $id): Purchase
    {
        return $this->handleServiceOperation(
            callback: function () use ($id) {
                $p = $this->findBranchPurchaseOrFail($id);
                $p->status = 'approved';
                $p->approved_at = now();
                $p->save();

                return $p;
            },
            operation: 'approve',
            context: ['purchase_id' => $id]
        );
    }

    public function receive(int $id): Purchase
    {
        return $this->handleServiceOperation(
            callback: function () use ($id) {
                $p = $this->findBranchPurchaseOrFail($id);
                $p->status = 'received';
                $p->received_at = now();
                $p->save();
                event(new \App\Events\PurchaseReceived($p));

                return $p;
            },
            operation: 'receive',
            context: ['purchase_id' => $id]
        );
    }

    public function pay(int $id, float $amount): Purchase
    {
        return $this->handleServiceOperation(
            callback: function () use ($id, $amount) {
                $p = $this->findBranchPurchaseOrFail($id);

                // Validate payment amount
                if ($amount <= 0) {
                    throw new \InvalidArgumentException('Payment amount must be positive');
                }

                // Use correct migration column names
                $remainingDue = max(0, (float) $p->total_amount - (float) $p->paid_amount);
                if ($amount > $remainingDue) {
                    throw new \InvalidArgumentException(sprintf(
                        'Payment amount (%.2f) exceeds remaining due (%.2f)',
                        $amount,
                        $remainingDue
                    ));
                }

                // Critical ERP: Use bcmath for precise money calculations
                $newPaidAmount = bcadd((string) $p->paid_amount, (string) $amount, 2);
                $p->paid_amount = (float) $newPaidAmount;

                // Update payment status
                if ((float) $p->paid_amount >= (float) $p->total_amount) {
                    $p->payment_status = 'paid';
                } elseif ((float) $p->paid_amount > 0) {
                    $p->payment_status = 'partial';
                } else {
                    $p->payment_status = 'unpaid';
                }
                $p->save();

                return $p;
            },
            operation: 'pay',
            context: ['purchase_id' => $id, 'amount' => $amount]
        );
    }

    public function cancel(int $id): Purchase
    {
        return $this->handleServiceOperation(
            callback: function () use ($id) {
                $p = $this->findBranchPurchaseOrFail($id);

                // Prevent cancelling if already received or paid
                if ($p->status === 'received') {
                    throw new \InvalidArgumentException('Cannot cancel a received purchase. Please create a return instead.');
                }
                if ($p->status === 'paid') {
                    throw new \InvalidArgumentException('Cannot cancel a paid purchase. Please refund first.');
                }
                if ($p->status === 'cancelled') {
                    throw new \InvalidArgumentException('Purchase is already cancelled.');
                }

                $p->status = 'cancelled';
                $p->save();

                return $p;
            },
            operation: 'cancel',
            context: ['purchase_id' => $id]
        );
    }
}
