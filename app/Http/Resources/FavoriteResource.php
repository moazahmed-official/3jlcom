<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteResource extends JsonResource
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
            'user_id' => $this->user_id,
            'ad_id' => $this->ad_id,
            'ad' => $this->whenLoaded('ad', function () {
                // Determine ad type and return appropriate resource
                $ad = $this->ad;
                if ($ad->adable_type === 'normal_ad') {
                    return new NormalAdResource($ad);
                } elseif ($ad->adable_type === 'unique_ad') {
                    return new UniqueAdResource($ad);
                } elseif ($ad->adable_type === 'caishha_ad') {
                    return new CaishhaAdResource($ad);
                } elseif ($ad->adable_type === 'auction_ad') {
                    return new AuctionAdResource($ad);
                }
                return new AdResource($ad);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
