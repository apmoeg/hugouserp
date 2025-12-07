<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product', fn () => new ProductResource($this->product)),
            'quantity' => (int) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'discount' => (float) $this->discount,
            'tax' => (float) ($this->tax ?? 0),
            'total' => (float) $this->total,
            'notes' => $this->notes,
        ];
    }
}
