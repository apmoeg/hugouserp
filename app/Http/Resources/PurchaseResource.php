<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'reference_no' => $this->reference_no,
            'branch_id' => $this->branch_id,
            'warehouse_id' => $this->warehouse_id,
            'supplier_id' => $this->supplier_id,
            'status' => $this->status,
            'expected_delivery_date' => $this->expected_delivery_date?->toIso8601String(),
            'actual_delivery_date' => $this->actual_delivery_date?->toIso8601String(),
            'shipping_method' => $this->shipping_method,
            'tracking_number' => $this->tracking_number,
            'supplier_notes' => $this->supplier_notes,
            'internal_notes' => $this->internal_notes,
            'payment_status' => $this->payment_status,
            'payment_due_date' => $this->payment_due_date?->toIso8601String(),
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value ? (float) $this->discount_value : null,
            'sub_total' => (float) ($this->sub_total ?? 0.0),
            'tax_total' => (float) ($this->tax_total ?? 0.0),
            'discount_total' => (float) ($this->discount_total ?? 0.0),
            'shipping_total' => (float) ($this->shipping_total ?? 0.0),
            'grand_total' => (float) ($this->grand_total ?? 0.0),
            'paid_total' => (float) ($this->paid_total ?? 0.0),
            'due_total' => (float) ($this->due_total ?? 0.0),
            'posted_at' => $this->posted_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'received_at' => $this->received_at?->toIso8601String(),
            'branch' => $this->whenLoaded('branch', fn () => new BranchResource($this->branch)),
            'supplier' => $this->whenLoaded('supplier', fn () => new SupplierResource($this->supplier)),
            'items' => $this->whenLoaded('items'),
            'items_count' => $this->whenCounted('items'),
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
