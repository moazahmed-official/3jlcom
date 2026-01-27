<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ModelResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'brand_id' => $this->brand_id,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'image' => $this->image,
            'image_url' => $this->image ? Storage::disk('public')->url($this->image) : null,
            'year_from' => $this->year_from,
            'year_to' => $this->year_to,
            'brand' => $this->whenLoaded('brand', fn() => new BrandResource($this->brand)),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}