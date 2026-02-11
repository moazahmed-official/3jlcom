<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        $package = $this->getCurrentPackage();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email ?? null,
            'phone' => $this->phone ?? null,
            'role' => $this->roles()->pluck('name')->first() ?? null,
            'status' => $this->status ?? 'active',
            'joined_date' => $this->created_at?->toDateString(),
            'location' => trim(($this->city?->name ?? '') . ' ' . ($this->country?->name ?? '')) ?: null,
            'country' => $this->country?->name ?? ($this->country_id ?? null),
            'city' => $this->city?->name ?? ($this->city_id ?? null),
            'average_rating' => $this->reviews_received_avg_rating ?? $this->avg_rating ?? 0.0,
            'reviews_count' => $this->reviews_received_count ?? $this->reviews_count ?? 0,
            'package' => $package ? [
                'id' => $package->id,
                'name' => $package->name,
            ] : null,
            'package_start_date' => optional($this->userPackages()->where('active', true)->latest()->first())->start_date?->toDateString() ?? null,
            'package_end_date' => optional($this->userPackages()->where('active', true)->latest()->first())->end_date?->toDateString() ?? null,
            'total_ads_count' => [
                 'unique' => $this->countActiveAdsByType('unique'),
                 'normal' => $this->countActiveAdsByType('normal'),
                 'caishha' => $this->countActiveAdsByType('caishha'),
                 'findit' => $this->finditRequests()->count(),
                 'auctions' => $this->countActiveAdsByType('auction'),
            ],
            'is_verified' => (bool) ($this->is_verified ?? false),
            'seller_verified' => (bool) ($this->seller_verified ?? false),
            'seller_verified_at' => $this->seller_verified_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
