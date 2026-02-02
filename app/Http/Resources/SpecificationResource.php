<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpecificationResource extends JsonResource
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
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'type' => $this->type,
            'values' => $this->values,
            'image_id' => $this->image_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Conditional relationships
            'image' => $this->whenLoaded('image', function () {
                return [
                    'id' => $this->image->id,
                    'url' => $this->image->url,
                    'type' => $this->image->type,
                ];
            }),
        ];
    }
}
