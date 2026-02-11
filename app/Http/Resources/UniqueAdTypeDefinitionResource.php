<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UniqueAdTypeDefinitionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'priority' => $this->priority,
            'active' => $this->active,
            
            // Features
            'features' => [
                'allows_frame' => $this->allows_frame,
                'allows_colored_frame' => $this->allows_colored_frame,
                'allows_image_frame' => $this->allows_image_frame,
                'auto_republish_enabled' => $this->auto_republish_enabled,
                'facebook_push_enabled' => $this->facebook_push_enabled,
                'caishha_feature_enabled' => $this->caishha_feature_enabled,
            ],
            
            // Future API credits
            'api_credits' => [
                'carseer_api_credits' => $this->carseer_api_credits,
                'auto_bg_credits' => $this->auto_bg_credits,
                'pixblin_credits' => $this->pixblin_credits,
            ],
            
            // Media limits
            'media_limits' => [
                'max_images' => $this->max_images,
                'max_videos' => $this->max_videos,
            ],
            
            // Custom text features
            'custom_features_text' => $this->custom_features_text ?? [],
            
            // Metadata
            'active_ads_count' => $this->when(
                $request->routeIs('admin.*') || $this->relationLoaded('uniqueAds'),
                fn() => $this->uniqueAds()->whereHas('ad', fn($q) => $q->where('status', 'published'))->count()
            ),
            
            'packages_count' => $this->when(
                $request->routeIs('admin.*') || $this->relationLoaded('packages'),
                fn() => $this->packages()->count()
            ),
            
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
