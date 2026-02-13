<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaishhaAdResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $caishhaAd = $this->caishhaAd;
        $isOwner = auth()->check() && auth()->id() === $this->user_id;
        $isAdmin = auth()->check() && auth()->user()->isAdmin();

        return [
            'id' => $this->id,
            'type' => 'caishha',
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'views_count' => $this->views_count ?? 0,
            'contact_count' => $this->contact_count ?? 0,
            'contact_phone' => $this->contact_phone,
            'whatsapp_number' => $this->whatsapp_number,
            'period_days' => $this->period_days,
            'is_pushed_facebook' => (bool) $this->is_pushed_facebook,
            
            // Ad classification
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'model_id' => $this->model_id,
            'year' => $this->year,
            'color' => $this->color,
            'millage' => $this->millage,
            'city_id' => $this->city_id,
            'country_id' => $this->country_id,
            'user_id' => $this->user_id,
            
            // Caishha-specific fields
            'offers_count' => $caishhaAd?->offers_count ?? 0,
            'offers_window_period' => $caishhaAd?->getDealerWindowPeriod(),
            'sellers_visibility_period' => $caishhaAd?->getVisibilityPeriod(),
            
            // Window status information
            'window_status' => $this->when($caishhaAd, function () use ($caishhaAd) {
                return [
                    'is_in_dealer_window' => $caishhaAd->isInDealerWindow(),
                    'is_in_individual_window' => $caishhaAd->isInIndividualWindow(),
                    'are_offers_visible_to_seller' => $caishhaAd->areOffersVisibleToSeller(),
                    'can_accept_offers' => $caishhaAd->canAcceptOffers(),
                    'dealer_window_ends_at' => $caishhaAd->getDealerWindowEndsAt()?->toISOString(),
                    'visibility_period_ends_at' => $caishhaAd->getVisibilityPeriodEndsAt()?->toISOString(),
                ];
            }),
            
            // Timestamps
            'published_at' => $this->published_at?->toISOString(),
            'expired_at' => $this->expired_at?->toISOString(),
            'archived_at' => $this->archived_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relationships (conditionally loaded)
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'profile_image_id' => $this->user->profile_image_id,
                    'seller_verified' => $this->user->seller_verified ?? false,
                ];
            }),
            
            'brand' => $this->whenLoaded('brand', function () {
                return [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                    'image' => $this->brand->image,
                ];
            }),
            
            'model' => $this->whenLoaded('model', function () {
                return [
                    'id' => $this->model->id,
                    'name' => $this->model->name,
                ];
            }),
            
            'city' => $this->whenLoaded('city', function () {
                return [
                    'id' => $this->city->id,
                    'name' => $this->city->name,
                ];
            }),
            
            'country' => $this->whenLoaded('country', function () {
                return [
                    'id' => $this->country->id,
                    'name' => $this->country->name,
                    'currency' => $this->country->currency ?? 'JOD',
                ];
            }),
            
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
            
            'specifications' => $this->whenLoaded('specifications', function () {
                return $this->specifications->map(function ($spec) {
                    return [
                        'id' => $spec->id,
                        'name_en' => $spec->name_en,
                        'name_ar' => $spec->name_ar,
                        'type' => $spec->type,
                        'value' => $spec->pivot->value,
                    ];
                });
            }),
            
            'media' => MediaResource::collection($this->whenLoaded('media')),
            
            // Offers - only show when authorized
            'offers' => $this->when(
                $this->relationLoaded('caishhaAd') && 
                $this->caishhaAd && 
                $this->caishhaAd->relationLoaded('offers') &&
                ($isAdmin || ($isOwner && $caishhaAd?->areOffersVisibleToSeller())),
                function () {
                    return CaishhaOfferResource::collection($this->caishhaAd->offers);
                }
            ),
            
            // Accepted offer (if any) - visible to owner/admin when visibility period passed
            'accepted_offer' => $this->when(
                $caishhaAd && ($isAdmin || ($isOwner && $caishhaAd->areOffersVisibleToSeller())),
                function () use ($caishhaAd) {
                    $acceptedOffer = $caishhaAd->acceptedOffer();
                    return $acceptedOffer ? new CaishhaOfferResource($acceptedOffer) : null;
                }
            ),
        ];
    }
}
