<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'user_id',
        'file_name',
        'path',
        'type',
        'status',
        'thumbnail_url',
        'related_resource',
        'related_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['url'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getUrlAttribute(): string
    {
        if (!$this->path) {
            return '';
        }

        // If running in HTTP request context, prefer building URL from request host
        if (app()->runningInConsole() === false && request()) {
            $base = request()->getSchemeAndHttpHost();
            return rtrim($base, '/') . '/' . ltrim('storage/' . $this->path, '/');
        }

        // Fallback to filesystem disk url (uses config/filesystems.php 'url')
        return Storage::disk('public')->url($this->path);
    }

    public function getThumbnailAttribute(): ?string
    {
        if (!$this->thumbnail_url) {
            return null;
        }

        if (app()->runningInConsole() === false && request()) {
            $base = request()->getSchemeAndHttpHost();
            return rtrim($base, '/') . '/' . ltrim('storage/' . $this->thumbnail_url, '/');
        }

        return Storage::disk('public')->url($this->thumbnail_url);
    }

    protected static function boot()
    {
        parent::boot();

        // Delete associated files when media record is deleted
        static::deleting(function ($media) {
            if ($media->path && Storage::disk('public')->exists($media->path)) {
                Storage::disk('public')->delete($media->path);
            }
            if ($media->thumbnail_url && Storage::disk('public')->exists($media->thumbnail_url)) {
                Storage::disk('public')->delete($media->thumbnail_url);
            }
        });
    }
}
