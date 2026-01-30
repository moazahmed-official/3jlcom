<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaishhaOfferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ad_id' => $this->ad_id,
            'user_id' => $this->user_id,
            'price' => (float) $this->price,
            'comment' => $this->comment,
            'status' => $this->status,
            'is_visible_to_seller' => (bool) $this->is_visible_to_seller,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // User information (conditionally loaded)
            // Only show user details to ad owner or admin viewing offers
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'profile_image_id' => $this->user->profile_image_id,
                    'seller_verified' => $this->user->seller_verified ?? false,
                    'account_type' => $this->user->account_type,
                ];
            }),
            
            // Ad information (when viewing user's own offers)
            'ad' => $this->whenLoaded('ad', function () {
                return [
                    'id' => $this->ad->id,
                    'title' => $this->ad->title,
                    'status' => $this->ad->status,
                    'brand_id' => $this->ad->brand_id,
                    'model_id' => $this->ad->model_id,
                    'year' => $this->ad->year,
                    'brand' => $this->ad->relationLoaded('brand') ? [
                        'id' => $this->ad->brand->id,
                        'name' => $this->ad->brand->name,
                    ] : null,
                    'model' => $this->ad->relationLoaded('model') ? [
                        'id' => $this->ad->model->id,
                        'name' => $this->ad->model->name,
                    ] : null,
                ];
            }),
        ];
    }
}
