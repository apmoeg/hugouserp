<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ReturnNote;
use App\Models\Sale;
use App\Services\Contracts\SaleServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\DB;

class SaleService implements SaleServiceInterface
{
    use HandlesServiceErrors;

    public function show(int $id): Sale
    {
        return $this->handleServiceOperation(
            callback: fn () => Sale::with('items')->findOrFail($id),
            operation: 'show',
            context: ['sale_id' => $id]
        );
    }

    /** Return items (full/partial). Negative movement is handled in listeners. */
    public function handleReturn(int $saleId, array $items, ?string $reason = null): ReturnNote
    {
        return $this->handleServiceOperation(
            callback: function () use ($saleId, $items, $reason) {
                $sale = Sale::with('items')->findOrFail($saleId);

                return DB::transaction(function () use ($sale, $items, $reason) {
                    $note = ReturnNote::create([
                        'sale_id' => $sale->getKey(),
                        'reason' => $reason,
                    ]);

                    $refund = 0.0;
                    foreach ($items as $it) {
                        $si = $sale->items->firstWhere('product_id', $it['product_id']);
                        if (! $si) {
                            continue;
                        }
                        $qty = min((float) $it['qty'], (float) $si->qty);
                        $line = $qty * (float) $si->price;
                        $refund += $line;
                    }

                    $sale->status = 'returned';
                    $sale->paid_total = max(0.0, (float) $sale->paid_total - $refund);
                    $sale->save();

                    $this->logServiceInfo('handleReturn', 'Sale return processed', [
                        'sale_id' => $sale->getKey(),
                        'return_note_id' => $note->getKey(),
                        'refund_amount' => $refund,
                    ]);

                    return $note;
                });
            },
            operation: 'handleReturn',
            context: ['sale_id' => $saleId, 'items_count' => count($items), 'reason' => $reason]
        );
    }

    public function voidSale(int $saleId, ?string $reason = null): Sale
    {
        return $this->handleServiceOperation(
            callback: function () use ($saleId, $reason) {
                $sale = Sale::findOrFail($saleId);
                $sale->status = 'void';
                $sale->notes = trim(($sale->notes ?? '')."\nVOID: ".$reason);
                $sale->save();

                $this->logServiceInfo('voidSale', 'Sale voided', [
                    'sale_id' => $sale->getKey(),
                    'reason' => $reason,
                ]);

                return $sale;
            },
            operation: 'voidSale',
            context: ['sale_id' => $saleId, 'reason' => $reason]
        );
    }

    /** Return array with printable path (PDF/HTML) using PrintingService */
    public function printInvoice(int $saleId): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($saleId) {
                $sale = Sale::with('items')->findOrFail($saleId);
                $printer = app(PrintingService::class);

                return $printer->renderPdfOrHtml('prints.sale', ['sale' => $sale], 'sale_'.$sale->id);
            },
            operation: 'printInvoice',
            context: ['sale_id' => $saleId],
            defaultValue: []
        );
    }
}
