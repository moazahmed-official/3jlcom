<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UniqueAdResource extends JsonResource
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
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'city_id' => $this->city_id,
            'country_id' => $this->country_id,
            'brand_id' => $this->brand_id,
            'model_id' => $this->model_id,
            'year' => $this->year,
            'contact_phone' => $this->contact_phone,
            'whatsapp_number' => $this->whatsapp_number,
            'views_count' => $this->views_count ?? 0,
            
            // Unique ad specific fields
            'banner_color' => $this->whenLoaded('uniqueAd', fn() => $this->uniqueAd?->banner_color),
            'is_auto_republished' => $this->whenLoaded('uniqueAd', fn() => $this->uniqueAd?->is_auto_republished ?? false),
            'is_verified_ad' => $this->whenLoaded('uniqueAd', fn() => $this->uniqueAd?->is_verified_ad ?? false),
            'is_featured' => $this->whenLoaded('uniqueAd', fn() => $this->uniqueAd?->is_featured ?? false),
            'featured_at' => $this->whenLoaded('uniqueAd', fn() => $this->uniqueAd?->featured_at),
            
            // Relationships
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'category' => $this->whenLoaded('category', fn() => [
                'id' => $this->category->id,
                'name' => $this->category->name ?? $this->category->name_en ?? null,
            ]),
            'city' => $this->whenLoaded('city', fn() => [
                'id' => $this->city->id,
                'name' => $this->city->name ?? $this->city->name_en ?? null,
            ]),
            'country' => $this->whenLoaded('country', fn() => [
                'id' => $this->country->id,
                'name' => $this->country->name ?? $this->country->name_en ?? null,
            ]),
            'brand' => $this->whenLoaded('brand', fn() => $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
            ] : null),
            'model' => $this->whenLoaded('model', fn() => $this->model ? [
                'id' => $this->model->id,
                'name' => $this->model->name,
            ] : null),
            'banner_image' => $this->whenLoaded('uniqueAd', function () {
                if ($this->uniqueAd?->bannerImage) {
                    return [
                        'id' => $this->uniqueAd->bannerImage->id,
                        'url' => $this->uniqueAd->bannerImage->url,
                    ];
                }
                return null;
            }),
            'media' => $this->whenLoaded('media', fn() => $this->media->map(fn($m) => [
                'id' => $m->id,
                'url' => $m->url,
                'type' => $m->type,
            ])),
            
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
