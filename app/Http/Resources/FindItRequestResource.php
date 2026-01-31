<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FindItRequestResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            
            // User info (owner)
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'profile_image' => $this->user->profile_image,
                ];
            }),
            'user_id' => $this->user_id,
            
            // Vehicle criteria
            'brand' => $this->whenLoaded('brand', function () {
                return [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                    'logo' => $this->brand->logo ?? null,
                ];
            }),
            'brand_id' => $this->brand_id,
            
            'model' => $this->whenLoaded('carModel', function () {
                return [
                    'id' => $this->carModel->id,
                    'name' => $this->carModel->name,
                ];
            }),
            'model_id' => $this->model_id,
            
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
            'category_id' => $this->category_id,
            
            // Price range
            'min_price' => $this->min_price,
            'max_price' => $this->max_price,
            'price_range' => $this->getPriceRangeText(),
            
            // Year range
            'min_year' => $this->min_year,
            'max_year' => $this->max_year,
            'year_range' => $this->getYearRangeText(),
            
            // Mileage range
            'min_mileage' => $this->min_mileage,
            'max_mileage' => $this->max_mileage,
            'mileage_range' => $this->getMileageRangeText(),
            
            // Location
            'city' => $this->whenLoaded('city', function () {
                return [
                    'id' => $this->city->id,
                    'name' => $this->city->name,
                ];
            }),
            'city_id' => $this->city_id,
            
            'country' => $this->whenLoaded('country', function () {
                return [
                    'id' => $this->country->id,
                    'name' => $this->country->name,
                    'code' => $this->country->code ?? null,
                ];
            }),
            'country_id' => $this->country_id,
            
            // Additional filters
            'transmission' => $this->transmission,
            'fuel_type' => $this->fuel_type,
            'body_type' => $this->body_type,
            'color' => $this->color,
            'condition' => $this->condition,
            'condition_label' => $this->getConditionLabel(),
            'condition_rating' => $this->condition_rating,
            
            // Media (reference images)
            'media' => $this->whenLoaded('media', function () {
                return $this->media->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'url' => $media->url,
                        'thumbnail' => $media->thumbnail ?? $media->url,
                        'type' => $media->type ?? 'image',
                    ];
                });
            }),
            
            // Counters
            'matches_count' => $this->matches_count ?? $this->whenCounted('matches'),
            
            // Matches (when included)
            'matches' => FindItMatchResource::collection($this->whenLoaded('matches')),
            
            // Timestamps
            'expires_at' => $this->expires_at?->toIso8601String(),
            'expires_in' => $this->expires_at ? $this->expires_at->diffForHumans() : null,
            'is_expired' => $this->isExpired(),
            'last_matched_at' => $this->last_matched_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            
            // Computed properties
            'is_active' => $this->isActive(),
            
            // Permissions (context-aware)
            'permissions' => $this->when(
                auth()->check(),
                fn() => $this->getPermissions()
            ),
        ];
    }

    /**
     * Get human-readable status label.
     */
    protected function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'active' => 'Active',
            'closed' => 'Closed',
            'expired' => 'Expired',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get formatted price range text.
     */
    protected function getPriceRangeText(): ?string
    {
        if ($this->min_price && $this->max_price) {
            return number_format($this->min_price) . ' - ' . number_format($this->max_price);
        }
        
        if ($this->min_price) {
            return 'From ' . number_format($this->min_price);
        }
        
        if ($this->max_price) {
            return 'Up to ' . number_format($this->max_price);
        }
        
        return null;
    }

    /**
     * Get formatted year range text.
     */
    protected function getYearRangeText(): ?string
    {
        if ($this->min_year && $this->max_year) {
            if ($this->min_year === $this->max_year) {
                return (string) $this->min_year;
            }
            return $this->min_year . ' - ' . $this->max_year;
        }
        
        if ($this->min_year) {
            return $this->min_year . '+';
        }
        
        if ($this->max_year) {
            return 'Up to ' . $this->max_year;
        }
        
        return null;
    }

    /**
     * Get formatted mileage range text.
     */
    protected function getMileageRangeText(): ?string
    {
        if ($this->min_mileage && $this->max_mileage) {
            return number_format($this->min_mileage) . ' - ' . number_format($this->max_mileage) . ' km';
        }
        
        if ($this->min_mileage) {
            return 'From ' . number_format($this->min_mileage) . ' km';
        }
        
        if ($this->max_mileage) {
            return 'Up to ' . number_format($this->max_mileage) . ' km';
        }
        
        return null;
    }

    /**
     * Get human-readable condition label.
     */
    protected function getConditionLabel(): ?string
    {
        $labels = [
            'new' => 'New',
            'excellent' => 'Excellent',
            'very_good' => 'Very Good',
            'good' => 'Good',
            'fair' => 'Fair',
            'poor' => 'Poor',
            'certified' => 'Certified Pre-Owned',
        ];

        return $labels[$this->condition] ?? null;
    }

    /**
     * Get permissions for the current user.
     */
    protected function getPermissions(): array
    {
        $user = auth()->user();
        $isOwner = $user && $this->user_id === $user->id;
        $isAdmin = $user && $user->hasRole('admin');

        return [
            'can_view' => $isOwner || $isAdmin,
            'can_edit' => $isOwner && !$this->isExpired(),
            'can_delete' => $isOwner || $isAdmin,
            'can_close' => $isOwner && $this->isActive(),
            'can_activate' => $isOwner && $this->status === 'draft' && !$this->isExpired(),
        ];
    }
}
