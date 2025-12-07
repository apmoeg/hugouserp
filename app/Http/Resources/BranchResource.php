<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'is_main' => $this->is_main,
            'settings' => $this->when($request->user()?->can('branches.settings'), $this->settings),
            'modules' => $this->whenLoaded('modules', fn () => ModuleResource::collection($this->modules)),
            'users_count' => $this->whenCounted('users'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
