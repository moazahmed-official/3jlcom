<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdUpgradeRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ad_id' => $this->ad_id,
            'ad' => $this->when($this->relationLoaded('ad'), function () {
                return [
                    'id' => $this->ad->id,
                    'title' => $this->ad->title,
                    'type' => $this->ad->type,
                    'status' => $this->ad->status,
                ];
            }),
            'requested_unique_type' => $this->when($this->relationLoaded('requestedType'), function () {
                return new UniqueAdTypeDefinitionResource($this->requestedType);
            }),
            'user' => $this->when($this->relationLoaded('user'), function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'status' => $this->status,
            'user_message' => $this->user_message,
            'admin_message' => $this->admin_message,
            'reviewer' => $this->when($this->relationLoaded('reviewer') && $this->reviewer, function () {
                return [
                    'id' => $this->reviewer->id,
                    'name' => $this->reviewer->name,
                ];
            }),
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
