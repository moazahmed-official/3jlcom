<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BidResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $isOwner = auth()->check() && auth()->id() === $this->user_id;
        $isAuctionOwner = auth()->check() && 
            $this->auction && 
            $this->auction->ad && 
            $this->auction->ad->user_id === auth()->id();
        $isAdmin = auth()->check() && auth()->user()->isAdmin();
        
        // Determine if we should show full user details
        $showUserDetails = $isOwner || $isAdmin;
        
        // Check if additional data specifies to show user details
        if (isset($this->additional['show_user_details'])) {
            $showUserDetails = $this->additional['show_user_details'];
        }

        return [
            'id' => $this->id,
            'auction_id' => $this->auction_id,
            'price' => (float) $this->price,
            'comment' => $this->comment,
            'status' => $this->status ?? 'active',
            'created_at' => $this->created_at?->toISOString(),
            'withdrawn_at' => $this->withdrawn_at?->toISOString(),
            
            // User information - anonymized for privacy unless authorized
            'user' => $this->when($showUserDetails, function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'profile_image_id' => $this->user->profile_image_id ?? null,
                ];
            }, function () use ($isAuctionOwner) {
                // Anonymized version for auction owner or public
                return [
                    'id' => $this->user_id,
                    'name' => $this->getAnonymizedBidderLabel(),
                    'profile_image_id' => null,
                ];
            }),
            
            // Additional flags
            'is_own_bid' => $isOwner,
            'is_highest_bid' => $this->isHighestBid(),
            'is_winning_bid' => $this->isWinningBid(),
            
            // Auction information (when loaded)
            'auction' => $this->whenLoaded('auction', function () {
                return [
                    'id' => $this->auction->id,
                    'status' => $this->auction->status,
                    'end_time' => $this->auction->end_time?->toISOString(),
                    'ad' => $this->when($this->auction->relationLoaded('ad'), function () {
                        return [
                            'id' => $this->auction->ad->id,
                            'title' => $this->auction->ad->title,
                            'type' => $this->auction->ad->type,
                        ];
                    }),
                ];
            }),
        ];
    }
}
