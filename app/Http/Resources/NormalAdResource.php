<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NormalAdResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'views_count' => $this->views_count ?? 0,
            'contact_phone' => $this->contact_phone,
            'whatsapp_number' => $this->whatsapp_number,
            'period_days' => $this->period_days,
            'is_pushed_facebook' => $this->is_pushed_facebook,
            'category_id' => $this->category_id,
            'city_id' => $this->city_id,
            'country_id' => $this->country_id,
            'brand_id' => $this->brand_id,
            'model_id' => $this->model_id,
            'year' => $this->year,
            'color' => $this->color,
            'millage' => $this->millage,
            'price_cash' => $this->normalAd?->price_cash,
            'installment_id' => $this->normalAd?->installment_id,
            'start_time' => $this->normalAd?->start_time,
            'update_time' => $this->normalAd?->update_time,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relationships
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'full_name' => $this->user->full_name,
                    'profile_image' => $this->user->profile_image,
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
            
            'installment' => $this->whenLoaded('normalAd.installment', function () {
                return [
                    'id' => $this->normalAd->installment->id,
                    'original_price' => $this->normalAd->installment->original_price,
                    'deposit_amount' => $this->normalAd->installment->deposit_amount,
                    'installment_amount' => $this->normalAd->installment->installment_amount,
                    'period_months' => $this->normalAd->installment->period_months,
                    'apr' => $this->normalAd->installment->apr,
                ];
            }),
            'capabilities' => [
                'can_publish' => true,
                'can_unpublish' => true,
                'can_archive' => true,
                // Normal ads do not support unique 'feature' flag
                'can_feature' => false,
            ],
        ];
    }
}