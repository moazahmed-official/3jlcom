<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $isAdmin = $request->user() && $request->user()->hasAnyRole(['admin', 'super_admin']);

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'price_formatted' => '$' . number_format($this->price, 2),
            'duration_days' => $this->duration_days,
            'duration_formatted' => $this->formatDuration($this->duration_days),
            'features' => $this->features ?? [],
            'is_free' => $this->isFree(),
            'active' => $this->active,
            'visibility_type' => $this->visibility_type ?? 'public',
            'is_visible_to_user' => $request->user() ? $this->isVisibleTo($request->user()) : $this->isPublic(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        // Include admin-only statistics and visibility details
        if ($isAdmin) {
            $data['active_subscribers_count'] = $this->active_subscribers_count ?? 
                $this->userPackages()->where('active', true)->count();
            $data['allowed_roles'] = $this->allowed_roles;
            
            // For user_specific packages, include count of users with access
            if ($this->visibility_type === 'user_specific') {
                $data['user_access_count'] = $this->userAccess()->count();
            }
        }

        return $data;
    }

    /**
     * Format duration in human-readable format
     */
    protected function formatDuration(int $days): string
    {
        if ($days === 0) {
            return 'Unlimited';
        }

        if ($days < 30) {
            return $days . ' ' . ($days === 1 ? 'day' : 'days');
        }

        if ($days < 365) {
            $months = round($days / 30);
            return $months . ' ' . ($months === 1 ? 'month' : 'months');
        }

        $years = round($days / 365, 1);
        return $years . ' ' . ($years == 1 ? 'year' : 'years');
    }
}
