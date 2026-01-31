<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Auction extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'auctions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'ad_id',
        'start_price',
        'last_price',
        'reserve_price',
        'minimum_bid_increment',
        'start_time',
        'end_time',
        'winner_user_id',
        'auto_close',
        'is_last_price_visible',
        'anti_snip_window_seconds',
        'anti_snip_extension_seconds',
        'status',
        'bid_count',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'start_price' => 'decimal:2',
        'last_price' => 'decimal:2',
        'reserve_price' => 'decimal:2',
        'minimum_bid_increment' => 'decimal:2',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'auto_close' => 'boolean',
        'is_last_price_visible' => 'boolean',
        'anti_snip_window_seconds' => 'integer',
        'anti_snip_extension_seconds' => 'integer',
        'bid_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Default attribute values.
     */
    protected $attributes = [
        'minimum_bid_increment' => 100,
        'anti_snip_window_seconds' => 300,
        'anti_snip_extension_seconds' => 300,
        'status' => 'active',
        'bid_count' => 0,
        'auto_close' => true,
        'is_last_price_visible' => true,
    ];

    /**
     * Get the base ad that this auction belongs to.
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class, 'ad_id');
    }

    /**
     * Get all bids for this auction, ordered by price descending.
     */
    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class, 'auction_id')->orderBy('price', 'desc');
    }

    /**
     * Get the winner user of this auction.
     */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    /**
     * Get the highest bid for this auction.
     */
    public function highestBid()
    {
        return $this->bids()->first();
    }

    /**
     * Check if the auction has started.
     */
    public function hasStarted(): bool
    {
        if (!$this->start_time) {
            return false;
        }
        return now()->greaterThanOrEqualTo($this->start_time);
    }

    /**
     * Check if the auction has ended.
     */
    public function hasEnded(): bool
    {
        if (!$this->end_time) {
            return false;
        }
        return now()->greaterThanOrEqualTo($this->end_time);
    }

    /**
     * Check if the auction is currently active and accepting bids.
     */
    public function isActive(): bool
    {
        if (!$this->start_time || !$this->end_time) {
            return false;
        }

        $now = now();
        $ad = $this->ad;

        return $now->greaterThanOrEqualTo($this->start_time) 
            && $now->lessThan($this->end_time)
            && $this->status === 'active'
            && $ad 
            && $ad->status === 'published';
    }

    /**
     * Check if the auction can accept new bids.
     */
    public function canAcceptBids(): bool
    {
        return $this->isActive() && !$this->winner_user_id;
    }

    /**
     * Get the time remaining in seconds until auction ends.
     */
    public function getTimeRemaining(): int
    {
        if ($this->hasEnded()) {
            return 0;
        }

        if (!$this->end_time) {
            return 0;
        }

        return (int) now()->diffInSeconds($this->end_time, false);
    }

    /**
     * Get the time remaining as a human-readable string.
     */
    public function getTimeRemainingForHumans(): string
    {
        if ($this->hasEnded()) {
            return 'Ended';
        }

        if (!$this->end_time) {
            return 'Not started';
        }

        return now()->diffForHumans($this->end_time, ['parts' => 2, 'short' => true]);
    }

    /**
     * Check if the reserve price has been met.
     */
    public function meetsReserve(): bool
    {
        // No reserve price means it's always met
        if (!$this->reserve_price || $this->reserve_price <= 0) {
            return true;
        }

        // Check if last_price meets reserve
        return $this->last_price && $this->last_price >= $this->reserve_price;
    }

    /**
     * Calculate the minimum next bid amount.
     */
    public function getMinimumNextBid(): float
    {
        $currentPrice = $this->last_price ?? $this->start_price ?? 0;
        $increment = $this->minimum_bid_increment ?? 100;

        // If no bids yet, minimum is start_price (or increment if no start_price)
        if (!$this->last_price) {
            return max($currentPrice, $increment);
        }

        return $currentPrice + $increment;
    }

    /**
     * Check if anti-sniping should be triggered for a new bid.
     */
    public function shouldTriggerAntiSnipe(): bool
    {
        if (!$this->end_time || !$this->anti_snip_window_seconds) {
            return false;
        }

        $secondsUntilEnd = $this->getTimeRemaining();
        return $secondsUntilEnd > 0 && $secondsUntilEnd <= $this->anti_snip_window_seconds;
    }

    /**
     * Extend the end time for anti-sniping.
     */
    public function extendEndTime(): void
    {
        if (!$this->anti_snip_extension_seconds) {
            return;
        }

        $this->end_time = $this->end_time->addSeconds($this->anti_snip_extension_seconds);
        $this->save();
    }

    /**
     * Close the auction and determine the winner.
     */
    public function closeAuction(): array
    {
        $result = [
            'success' => false,
            'winner_id' => null,
            'winning_bid' => null,
            'reserve_met' => false,
            'message' => '',
        ];

        // Get the highest bid
        $highestBid = $this->highestBid();

        if (!$highestBid) {
            $this->status = 'closed';
            $this->save();
            
            $result['success'] = true;
            $result['message'] = 'Auction closed with no bids';
            return $result;
        }

        $result['winning_bid'] = $highestBid->price;
        $result['reserve_met'] = $this->meetsReserve();

        if ($result['reserve_met']) {
            $this->winner_user_id = $highestBid->user_id;
            $result['winner_id'] = $highestBid->user_id;
            $result['message'] = 'Auction closed successfully with winner';
        } else {
            $result['message'] = 'Auction closed - reserve price not met';
        }

        $this->status = 'closed';
        $this->save();

        // Update ad status
        if ($this->ad) {
            $this->ad->status = 'expired';
            $this->ad->expired_at = now();
            $this->ad->save();
        }

        $result['success'] = true;
        return $result;
    }

    /**
     * Cancel the auction.
     */
    public function cancelAuction(): bool
    {
        if ($this->status === 'closed') {
            return false;
        }

        $this->status = 'cancelled';
        $this->save();

        // Update ad status
        if ($this->ad) {
            $this->ad->status = 'removed';
            $this->ad->save();
        }

        return true;
    }

    /**
     * Scope to get active auctions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_time', '<=', now())
            ->where('end_time', '>', now());
    }

    /**
     * Scope to get closed auctions.
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /**
     * Scope to get auctions ready for auto-close.
     */
    public function scopeReadyForAutoClose($query)
    {
        return $query->where('status', 'active')
            ->where('auto_close', true)
            ->where('end_time', '<=', now());
    }

    /**
     * Scope to get auctions ending soon.
     */
    public function scopeEndingSoon($query, int $minutes = 60)
    {
        return $query->where('status', 'active')
            ->where('end_time', '>', now())
            ->where('end_time', '<=', now()->addMinutes($minutes));
    }
}
