<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category_id' => $this->category_id,
            'image_id' => $this->image_id,
            // Body may be stored as HTML/text or as JSON-encoded blocks. Decode if JSON.
            'body' => $this->when(
                true,
                function () {
                    if (is_string($this->body)) {
                        $decoded = json_decode($this->body, true);
                        return json_last_error() === JSON_ERROR_NONE ? $decoded : $this->body;
                    }
                    return $this->body;
                }
            ),
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relationships
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
            
            'image' => $this->whenLoaded('image', function () {
                return [
                    'id' => $this->image->id,
                    'url' => $this->image->url,
                    'type' => $this->image->type,
                ];
            }),
            
        ];
    }
}
