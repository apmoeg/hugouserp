<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\Contracts\PurchaseServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\DB;

class PurchaseService implements PurchaseServiceInterface
{
    use HandlesServiceErrors;

    public function create(array $payload): Purchase
    {
        return $this->handleServiceOperation(
            callback: fn () => DB::transaction(function () use ($payload) {
                $p = Purchase::create([
                    'branch_id' => request()->attributes->get('branch_id'),
                    'warehouse_id' => $payload['warehouse_id'] ?? null,
                    'supplier_id' => $payload['supplier_id'] ?? null,
                    'status' => 'draft',
                    'subtotal' => 0, 'tax_total' => 0, 'discount_total' => 0, 'total' => 0,
                ]);
                foreach ($payload['items'] ?? [] as $it) {
                    PurchaseItem::create([
                        'purchase_id' => $p->getKey(),
                        'product_id' => $it['product_id'],
                        'qty' => (float) $it['qty'],
                        'price' => (float) ($it['price'] ?? 0),
                        'total' => (float) ($it['qty'] * ($it['price'] ?? 0)),
                    ]);
                }
                $p->subtotal = (float) $p->items()->sum('total');
                $p->total = $p->subtotal;
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
                $p = Purchase::findOrFail($id);
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
                $p = Purchase::findOrFail($id);
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
                $p = Purchase::findOrFail($id);
                $p->paid_total = round((float) $p->paid_total + $amount, 2);
                if ($p->paid_total >= $p->total) {
                    $p->status = 'paid';
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
                $p = Purchase::findOrFail($id);
                $p->status = 'cancelled';
                $p->save();

                return $p;
            },
            operation: 'cancel',
            context: ['purchase_id' => $id]
        );
    }
}
