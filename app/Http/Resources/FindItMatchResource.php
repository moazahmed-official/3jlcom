<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FindItMatchResource extends JsonResource
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
            
            // Related FindIt request
            'findit_request_id' => $this->findit_request_id,
            'findit_request' => $this->whenLoaded('finditRequest', function () {
                return [
                    'id' => $this->finditRequest->id,
                    'title' => $this->finditRequest->title,
                ];
            }),
            
            // Matched ad details
            'ad' => $this->whenLoaded('ad', function () {
                return [
                    'id' => $this->ad->id,
                    'title' => $this->ad->title,
                    'description' => \Str::limit($this->ad->description, 200),
                    'price' => $this->ad->price,
                    'formatted_price' => number_format($this->ad->price),
                    'year' => $this->ad->year ?? null,
                    'mileage' => $this->ad->mileage ?? null,
                    'formatted_mileage' => $this->ad->mileage ? number_format($this->ad->mileage) . ' km' : null,
                    'transmission' => $this->ad->transmission ?? null,
                    'fuel_type' => $this->ad->fuel_type ?? null,
                    'condition' => $this->ad->condition ?? null,
                    'thumbnail' => $this->getAdThumbnail(),
                    'media_count' => $this->ad->media_count ?? $this->ad->media()->count(),
                    
                    // Brand & Model
                    'brand' => $this->ad->brand ? [
                        'id' => $this->ad->brand->id,
                        'name' => $this->ad->brand->name,
                        'logo' => $this->ad->brand->logo ?? null,
                    ] : null,
                    'model' => $this->ad->model ? [
                        'id' => $this->ad->model->id,
                        'name' => $this->ad->model->name,
                    ] : null,
                    
                    // Location
                    'city' => $this->ad->city ? [
                        'id' => $this->ad->city->id,
                        'name' => $this->ad->city->name,
                    ] : null,
                    
                    // Seller info
                    'seller' => $this->ad->user ? [
                        'id' => $this->ad->user->id,
                        'name' => $this->ad->user->name,
                        'is_verified' => $this->ad->user->seller_verified ?? false,
                    ] : null,
                    
                    // Ad timestamps
                    'created_at' => $this->ad->created_at->toIso8601String(),
                ];
            }),
            'ad_id' => $this->ad_id,
            
            // Match scoring
            'match_score' => $this->match_score,
            'match_percentage' => $this->match_score . '%',
            'match_quality' => $this->getMatchQuality(),
            
            // Match criteria breakdown (how the score was calculated)
            'match_breakdown' => $this->when(
                $this->relationLoaded('ad') && $this->relationLoaded('finditRequest'),
                fn() => $this->getMatchBreakdown()
            ),
            
            // Status
            'dismissed' => (bool) $this->dismissed,
            'notified' => (bool) $this->notified_at,
            'notified_at' => $this->notified_at?->toIso8601String(),
            
            // Timestamps
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            
            // Permissions
            'permissions' => $this->when(
                auth()->check(),
                fn() => $this->getPermissions()
            ),
        ];
    }

    /**
     * Get the ad thumbnail URL.
     */
    protected function getAdThumbnail(): ?string
    {
        if (!$this->ad) {
            return null;
        }

        // Try to get thumbnail from ad
        if ($this->ad->thumbnail) {
            return $this->ad->thumbnail;
        }

        // Try to get first media
        if ($this->ad->relationLoaded('media') && $this->ad->media->isNotEmpty()) {
            $firstMedia = $this->ad->media->first();
            return $firstMedia->thumbnail ?? $firstMedia->url ?? null;
        }

        return null;
    }

    /**
     * Get match quality label based on score.
     */
    protected function getMatchQuality(): string
    {
        return match (true) {
            $this->match_score >= 90 => 'excellent',
            $this->match_score >= 75 => 'very_good',
            $this->match_score >= 60 => 'good',
            $this->match_score >= 40 => 'fair',
            default => 'partial',
        };
    }

    /**
     * Get detailed breakdown of how the match score was calculated.
     */
    protected function getMatchBreakdown(): array
    {
        if (!$this->ad || !$this->finditRequest) {
            return [];
        }

        $breakdown = [];
        $request = $this->finditRequest;
        $ad = $this->ad;

        // Brand match (+30 points)
        if ($request->brand_id) {
            $breakdown['brand'] = [
                'requested' => $request->brand->name ?? $request->brand_id,
                'matched' => $ad->brand->name ?? $ad->brand_id ?? 'N/A',
                'matches' => $request->brand_id === $ad->brand_id,
                'weight' => 30,
            ];
        }

        // Model match (+25 points)
        if ($request->model_id) {
            $breakdown['model'] = [
                'requested' => $request->model->name ?? $request->model_id,
                'matched' => $ad->model->name ?? $ad->model_id ?? 'N/A',
                'matches' => $request->model_id === $ad->model_id,
                'weight' => 25,
            ];
        }

        // Price match (+20 points)
        if ($request->min_price || $request->max_price) {
            $priceMatches = true;
            if ($request->min_price && $ad->price < $request->min_price) {
                $priceMatches = false;
            }
            if ($request->max_price && $ad->price > $request->max_price) {
                $priceMatches = false;
            }
            
            $breakdown['price'] = [
                'requested_range' => [
                    'min' => $request->min_price,
                    'max' => $request->max_price,
                ],
                'matched' => $ad->price,
                'matches' => $priceMatches,
                'weight' => 20,
            ];
        }

        // Year match (+15 points)
        if ($request->min_year || $request->max_year) {
            $yearMatches = true;
            $adYear = $ad->year ?? null;
            
            if ($adYear) {
                if ($request->min_year && $adYear < $request->min_year) {
                    $yearMatches = false;
                }
                if ($request->max_year && $adYear > $request->max_year) {
                    $yearMatches = false;
                }
            } else {
                $yearMatches = false;
            }
            
            $breakdown['year'] = [
                'requested_range' => [
                    'min' => $request->min_year,
                    'max' => $request->max_year,
                ],
                'matched' => $adYear,
                'matches' => $yearMatches,
                'weight' => 15,
            ];
        }

        // City/Location match (+10 points)
        if ($request->city_id) {
            $breakdown['city'] = [
                'requested' => $request->city->name ?? $request->city_id,
                'matched' => $ad->city->name ?? $ad->city_id ?? 'N/A',
                'matches' => $request->city_id === $ad->city_id,
                'weight' => 10,
            ];
        }

        return $breakdown;
    }

    /**
     * Get permissions for the current user.
     */
    protected function getPermissions(): array
    {
        $user = auth()->user();
        
        // Load FindIt request to check ownership
        $finditRequest = $this->finditRequest ?? $this->relationLoaded('finditRequest') 
            ? null 
            : \App\Models\FinditRequest::find($this->findit_request_id);
        
        $isRequestOwner = $user && $finditRequest && $finditRequest->user_id === $user->id;
        $isAdmin = $user && $user->hasRole('admin');

        return [
            'can_view_ad' => true, // Matched ads are visible to the request owner
            'can_dismiss' => ($isRequestOwner || $isAdmin) && !$this->dismissed,
            'can_contact_seller' => $isRequestOwner && $this->relationLoaded('ad') && $this->ad?->user_id,
        ];
    }
}
