<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminSentNotification extends Model
{
    use HasFactory;

    protected $table = 'admin_sent_notifications';

    protected $fillable = [
        'sent_by', 'title', 'body', 'data', 'image', 'target', 'target_role', 'recipients', 'recipients_count', 'action_url',
    ];

    protected $casts = [
        'data' => 'array',
        'recipients' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['action_url'];

    /**
     * Return the action URL for this sent notification.
     * Prefer the explicit column if present, otherwise fallback to the stored data payload.
     */
    public function getActionUrlAttribute(): ?string
    {
        if (!empty($this->attributes['action_url'])) {
            return $this->attributes['action_url'];
        }

        if (isset($this->data) && is_array($this->data) && !empty($this->data['action_url'])) {
            return $this->data['action_url'];
        }

        return null;
    }
}
