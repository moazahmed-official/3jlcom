<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->getTypeLabel(),
            'type_raw' => class_basename($this->type),
            'title' => $this->data['title'] ?? null,
            'body' => $this->data['body'] ?? $this->data['message'] ?? null,
            'data' => $this->formatData($this->data),
            'read' => !is_null($this->read_at),
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Get human-readable type label
     */
    protected function getTypeLabel(): string
    {
        $typeMap = [
            'SendOtpNotification' => 'otp',
            'ReviewReceivedNotification' => 'review_received',
            'ReportResolvedNotification' => 'report_resolved',
            'FindItNewMatchNotification' => 'findit_match',
            'FindItNewOfferNotification' => 'findit_offer',
            'FindItOfferAcceptedNotification' => 'findit_offer_accepted',
            'FindItOfferRejectedNotification' => 'findit_offer_rejected',
            'AdminSellerVerificationRequestNotification' => 'seller_verification',
            'AdminNotification' => 'admin_message',
        ];

        $className = class_basename($this->type);
        return $typeMap[$className] ?? strtolower($className);
    }

    /**
     * Format notification data, removing sensitive fields
     */
    protected function formatData(array $data): array
    {
        // Remove sensitive fields
        $sensitiveKeys = ['otp', 'otp_code', 'password', 'token'];
        
        return collect($data)
            ->except($sensitiveKeys)
            ->toArray();
    }
}
