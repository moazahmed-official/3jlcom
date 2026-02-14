<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Traits\ResolvesStorageUrl;

class BrandResource extends JsonResource
{
    use ResolvesStorageUrl;
    public function toArray($request): array
    {
        $mediaUrl = null;
        if (isset($this->resource) && method_exists($this->resource, 'relationLoaded') && $this->resource->relationLoaded('imageMedia')) {
            $mediaUrl = $this->imageMedia?->url;
        }

        $rawUrl = $mediaUrl ?? ($this->image ? Storage::disk('public')->url($this->image) : null);

        return [
            'id' => $this->id,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'image' => $this->image,
            'image_id' => $this->image_id ?? null,
            'image_url' => $this->resolveStorageUrl($rawUrl),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}