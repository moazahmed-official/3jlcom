<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'package_id' => $this->package_id,
            'status' => $this->status,
            'user_message' => $this->user_message,
            'admin_notes' => $this->admin_notes,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'package' => new PackageResource($this->whenLoaded('package')),
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
        ];
    }
}
