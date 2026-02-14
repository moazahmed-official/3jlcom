<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Traits\ResolvesStorageUrl;

class CategoryResource extends JsonResource
{
    use ResolvesStorageUrl;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'status' => $this->status,
            'specs_group_id' => $this->specs_group_id,
            'specifications' => $this->whenLoaded('specifications', function () {
                return $this->specifications->map(function ($spec) {
                    $image = null;
                    if (method_exists($spec, 'relationLoaded') && $spec->relationLoaded('image') && $spec->image) {
                        $image = [
                            'id' => $spec->image->id,
                            'url' => $this->resolveStorageUrl($spec->image->url),
                            'type' => $spec->image->type,
                        ];
                    }

                    return [
                        'id' => $spec->id,
                        'name_en' => $spec->name_en,
                        'name_ar' => $spec->name_ar,
                        'type' => $spec->type,
                        'order' => $spec->pivot->order,
                        'image' => $image,
                    ];
                });
            }),
            'specifications_count' => $this->whenLoaded('specifications', function () {
                return $this->specifications->count();
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
