<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BrandResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'image' => $this->image,
            'image_url' => $this->image ? Storage::disk('public')->url($this->image) : null,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}