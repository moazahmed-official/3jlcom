<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
            'target_type' => $this->target_type,
            'target_id' => $this->target_id,
            'stars' => $this->stars,
            'title' => $this->title,
            'body' => $this->body,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // User who created the review
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'profile_image' => $this->user->profile_image_id ? '/storage/media/' . $this->user->profile_image_id : null,
                ];
            }),

            // The target being reviewed (ad or seller)
            'target' => $this->when($this->relationLoaded('ad') || $this->relationLoaded('seller'), function () {
                if ($this->ad_id && $this->relationLoaded('ad')) {
                    return [
                        'type' => 'ad',
                        'id' => $this->ad->id,
                        'title' => $this->ad->title,
                    ];
                }
                
                if ($this->seller_id && $this->relationLoaded('seller')) {
                    return [
                        'type' => 'seller',
                        'id' => $this->seller->id,
                        'name' => $this->seller->name,
                    ];
                }
                
                return null;
            }),

            // Permissions for current user
            'permissions' => $this->when(auth()->check(), function () {
                return [
                    'can_edit' => auth()->id() === $this->user_id || auth()->user()->isAdmin(),
                    'can_delete' => auth()->id() === $this->user_id || auth()->user()->isAdmin(),
                ];
            }),
        ];
    }
}
