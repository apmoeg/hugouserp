<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'phone2' => $this->phone2,
            'company' => $this->company,
            'company_name' => $this->company_name,
            'customer_type' => $this->customer_type,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'tax_number' => $this->tax_number,
            'credit_limit' => $this->when(
                $request->user()?->can('customers.view-financial'),
                (float) ($this->credit_limit ?? 0.0)
            ),
            'discount_percentage' => $this->when(
                $request->user()?->can('customers.view-financial'),
                (float) ($this->discount_percentage ?? 0.0)
            ),
            'payment_terms' => $this->payment_terms,
            'payment_terms_days' => (int) ($this->payment_terms_days ?? 30),
            'payment_due_days' => (int) ($this->payment_due_days ?? 30),
            'customer_group' => $this->customer_group,
            'preferred_payment_method' => $this->preferred_payment_method,
            'last_order_date' => $this->last_order_date?->toIso8601String(),
            'current_balance' => $this->when(
                $request->user()?->can('customers.view-financial'),
                (float) ($this->current_balance ?? 0.0)
            ),
            'is_active' => (bool) $this->is_active,
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch', fn () => new BranchResource($this->branch)),
            'sales_count' => $this->when(
                $request->user()?->can('customers.view-sales'),
                $this->whenCounted('sales')
            ),
            'total_purchases' => $this->when(
                $request->user()?->can('customers.view-financial') && $this->relationLoaded('sales'),
                fn () => (float) $this->sales->sum('grand_total')
            ),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
