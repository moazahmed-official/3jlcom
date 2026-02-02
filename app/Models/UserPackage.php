<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class UserPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'start_date',
        'end_date',
        'active',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'package_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'active' => true,
    ];

    /**
     * Get the user that owns this package subscription
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the package for this subscription
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Scope to get only active subscriptions
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('user_packages.active', true);
    }

    /**
     * Scope to get expired subscriptions
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('user_packages.end_date', '<', now()->toDateString());
    }

    /**
     * Scope to get valid (active and not expired) subscriptions
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('user_packages.active', true)
            ->where(function ($q) {
                $q->whereNull('user_packages.end_date')
                  ->orWhere('user_packages.end_date', '>=', now()->toDateString());
            });
    }

    /**
     * Scope to filter by user
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by package
     */
    public function scopeForPackage(Builder $query, int $packageId): Builder
    {
        return $query->where('package_id', $packageId);
    }

    /**
     * Check if this subscription is currently valid
     */
    public function isValid(): bool
    {
        if (!$this->active) {
            return false;
        }

        if ($this->end_date === null) {
            return true;
        }

        return $this->end_date->gte(now()->startOfDay());
    }

    /**
     * Check if this subscription is expired
     */
    public function isExpired(): bool
    {
        if ($this->end_date === null) {
            return false;
        }

        return $this->end_date->lt(now()->startOfDay());
    }

    /**
     * Get remaining days for this subscription
     */
    public function getRemainingDaysAttribute(): ?int
    {
        if ($this->end_date === null) {
            return null; // Unlimited
        }

        $remaining = now()->startOfDay()->diffInDays($this->end_date, false);
        return max(0, $remaining);
    }

    /**
     * Activate this subscription
     */
    public function activate(): bool
    {
        $this->active = true;
        return $this->save();
    }

    /**
     * Deactivate this subscription
     */
    public function deactivate(): bool
    {
        $this->active = false;
        return $this->save();
    }

    /**
     * Extend this subscription by additional days
     */
    public function extend(int $days): bool
    {
        $baseDate = $this->end_date && $this->end_date->gte(now()) 
            ? $this->end_date 
            : now();
        
        $this->end_date = $baseDate->addDays($days);
        return $this->save();
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Calculate end_date based on package duration when creating
        static::creating(function (UserPackage $userPackage) {
            if ($userPackage->start_date === null) {
                $userPackage->start_date = now()->toDateString();
            }

            if ($userPackage->end_date === null && $userPackage->package) {
                $durationDays = $userPackage->package->duration_days;
                if ($durationDays > 0) {
                    $userPackage->end_date = Carbon::parse($userPackage->start_date)
                        ->addDays($durationDays)
                        ->toDateString();
                }
            }
        });
    }
}
