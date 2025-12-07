<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number ?? $this->id,
            'customer' => $this->whenLoaded('customer', fn () => new CustomerResource($this->customer)),
            'customer_id' => $this->customer_id,
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch', fn () => new BranchResource($this->branch)),
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'items' => $this->whenLoaded('items', fn () => OrderItemResource::collection($this->items)),
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount,
            'tax' => (float) $this->tax,
            'total' => (float) $this->total,
            'paid_amount' => (float) ($this->paid_amount ?? 0),
            'due_amount' => (float) (($this->total ?? 0) - ($this->paid_amount ?? 0)),
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'source' => $this->source ?? 'pos',
            'external_reference' => $this->external_reference,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
