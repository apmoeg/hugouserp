<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'locale' => $this->locale,
            'timezone' => $this->timezone,
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch', fn () => new BranchResource($this->branch)),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'permissions' => $this->when(
                $this->relationLoaded('roles'),
                fn () => $this->getAllPermissions()->pluck('name')
            ),
            'branches' => $this->whenLoaded('branches', fn () => BranchResource::collection($this->branches)),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
