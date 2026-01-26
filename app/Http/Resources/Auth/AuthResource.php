<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = $this->resource;

        return [
            'success' => true,
            'message' => $data['message'] ?? 'Authenticated',
            'data' => [
                'token' => $data['token'] ?? null,
                'token_type' => $data['token_type'] ?? 'Bearer',
                'expires_in' => $data['expires_in'] ?? null,
                'user' => new \App\Http\Resources\UserResource($data['user']),
            ],
        ];
    }
}
