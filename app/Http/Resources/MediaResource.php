<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->file_name,
            'path' => $this->path,
            'url' => $this->url,
            'type' => $this->type,
            'status' => $this->status,
            'thumbnail_url' => $this->thumbnail,
            'user_id' => $this->user_id,
            'related_resource' => $this->related_resource,
            'related_id' => $this->related_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}