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
            // Provide category name directly when relation is loaded
            'category_name' => $this->whenLoaded('category', function () {
                return $this->category->name_en ?? $this->category->name_ar ?? null;
            }),
            'order' => $this->order,
            'value' => $this->value,
            'status' => $this->status,
            'is_active' => $this->isActive(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Conditional relationships
            'media' => $this->whenLoaded('media', function () {
                return new MediaResource($this->media);
            }),
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name_en' => $this->category->name_en,
                    'name_ar' => $this->category->name_ar,
                ];
            }),
        ];
    }
}
