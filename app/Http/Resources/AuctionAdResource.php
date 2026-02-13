<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuctionAdResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $auction = $this->auction;
        $isOwner = auth()->check() && auth()->id() === $this->user_id;
        $isAdmin = auth()->check() && auth()->user()->isAdmin();

        return [
            'id' => $this->id,
            'type' => 'auction',
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
            'city_id' => $this->city_id,
            'country_id' => $this->country_id,
            'user_id' => $this->user_id,
            
            // Auction-specific fields
            'start_price' => $auction?->start_price ? (float) $auction->start_price : null,
            'last_price' => $this->when(
                $auction && ($auction->is_last_price_visible || $isOwner || $isAdmin),
                fn() => $auction->last_price ? (float) $auction->last_price : null
            ),
            'reserve_price' => $this->when(
                $isOwner || $isAdmin,
                fn() => $auction?->reserve_price ? (float) $auction->reserve_price : null
            ),
            'minimum_bid_increment' => $auction?->minimum_bid_increment ? (float) $auction->minimum_bid_increment : 100,
            'minimum_next_bid' => $auction?->getMinimumNextBid(),
            
            // Time fields
            'start_time' => $auction?->start_time?->toISOString(),
            'end_time' => $auction?->end_time?->toISOString(),
            
            // Auction status information
            'auction_status' => $auction?->status,
            'bid_count' => $auction?->bid_count ?? 0,
            'winner_user_id' => $this->when(
                $auction && $auction->status === 'closed',
                fn() => $auction->winner_user_id
            ),
            
            // Auction settings (visible to owner/admin)
            'auto_close' => $auction?->auto_close,
            'is_last_price_visible' => $auction?->is_last_price_visible,
            'anti_snip_window_seconds' => $this->when(
                $isOwner || $isAdmin,
                fn() => $auction?->anti_snip_window_seconds
            ),
            'anti_snip_extension_seconds' => $this->when(
                $isOwner || $isAdmin,
                fn() => $auction?->anti_snip_extension_seconds
            ),
            
            // Computed status fields
            'auction_state' => $this->when($auction, function () use ($auction) {
                return [
                    'is_active' => $auction->isActive(),
                    'has_started' => $auction->hasStarted(),
                    'has_ended' => $auction->hasEnded(),
                    'can_accept_bids' => $auction->canAcceptBids(),
                    'time_remaining_seconds' => $auction->getTimeRemaining(),
                    'time_remaining_human' => $auction->getTimeRemainingForHumans(),
                    'meets_reserve' => $auction->meetsReserve(),
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
            
            // Winner information (only when auction is closed)
            'winner' => $this->when(
                $auction && 
                $auction->status === 'closed' && 
                $auction->winner_user_id && 
                $this->relationLoaded('auction') &&
                $auction->relationLoaded('winner'),
                function () use ($auction) {
                    return [
                        'id' => $auction->winner->id,
                        'name' => $auction->winner->name,
                    ];
                }
            ),
            
            // Bids - only show when authorized (owner or admin)
            'bids' => $this->when(
                ($isAdmin || $isOwner) && 
                $this->relationLoaded('auction') && 
                $auction && 
                $auction->relationLoaded('bids'),
                function () use ($auction, $isAdmin) {
                    return BidResource::collection($auction->bids)
                        ->additional(['show_user_details' => $isAdmin]);
                }
            ),
            
            // User's own bid (if authenticated and has bid)
            'my_bid' => $this->when(
                auth()->check() && 
                $this->relationLoaded('auction') && 
                $auction && 
                $auction->relationLoaded('bids'),
                function () use ($auction) {
                    $myBid = $auction->bids->where('user_id', auth()->id())->first();
                    return $myBid ? new BidResource($myBid) : null;
                }
            ),
        ];
    }
}
