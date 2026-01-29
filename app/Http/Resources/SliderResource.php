<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SliderResource extends JsonResource
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
            'name' => $this->name,
            'image_id' => $this->image_id,
            'image_url' => $this->image_url,
            'category_id' => $this->category_id,
            'value' => $this->value,
            'status' => $this->status,
            'is_active' => $this->isActive(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Conditional relationships
            'media' => $this->whenLoaded('media', function () {
                return new MediaResource($this->media);
            }),
            'category' => $this->whenLoaded('category'),
        ];
    }
}
