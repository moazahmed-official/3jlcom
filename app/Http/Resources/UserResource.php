<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'account_type' => $this->account_type ?? null,
            'is_verified' => (bool) ($this->is_verified ?? false),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
