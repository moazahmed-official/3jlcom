<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isAdmin = auth()->check() && auth()->user()->hasAnyRole(['admin', 'super-admin', 'moderator']);
        $isOwner = auth()->check() && auth()->id() === $this->reported_by_user_id;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'reason' => $this->reason,
            'target_type' => $this->target_type,
            'target_id' => $this->target_id,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Reporter information (hidden from non-admins for privacy)
            'reporter' => $this->when($isAdmin || $isOwner, function () {
                return $this->whenLoaded('reporter', function () {
                    return [
                        'id' => $this->reporter->id,
                        'name' => $this->reporter->name,
                    ];
                });
            }),

            // Target entity details
            'target' => $this->whenLoaded('target', function () {
                if ($this->target_type === 'ad' && $this->target) {
                    return [
                        'type' => 'ad',
                        'id' => $this->target->id,
                        'title' => $this->target->title ?? 'Deleted Ad',
                    ];
                }

                if (in_array($this->target_type, ['user', 'dealer']) && $this->target) {
                    return [
                        'type' => $this->target_type,
                        'id' => $this->target->id,
                        'name' => $this->target->name ?? 'Deleted User',
                    ];
                }

                return null;
            }),

            // Assigned moderator (admin only)
            'assigned_to' => $this->when($isAdmin, function () {
                return $this->whenLoaded('assignedTo', function () {
                    if (!$this->assignedTo) {
                        return null;
                    }

                    return [
                        'id' => $this->assignedTo->id,
                        'name' => $this->assignedTo->name,
                    ];
                });
            }),

            // Status details
            'status_label' => $this->getStatusLabel(),
            'is_pending' => $this->isPending(),
            'is_assigned' => $this->isAssigned(),

            // Permissions for current user
            'permissions' => $this->when(auth()->check(), function () use ($isAdmin) {
                return [
                    'can_view' => $isAdmin || auth()->id() === $this->reported_by_user_id,
                    'can_assign' => $isAdmin,
                    'can_update_status' => $isAdmin || ($this->assigned_to === auth()->id()),
                    'can_delete' => $isAdmin,
                ];
            }),
        ];
    }

    /**
     * Get human-readable status label
     */
    protected function getStatusLabel(): string
    {
        return match($this->status) {
            'open' => 'Open',
            'under_review' => 'Under Review',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
            default => ucfirst($this->status),
        };
    }
}
