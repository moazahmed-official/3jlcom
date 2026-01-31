<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bid extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'bids';

    /**
     * Indicates if the model should be timestamped.
     * We only have created_at in the bids table.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'auction_id',
        'user_id',
        'price',
        'comment',
        'status',
        'withdrawn_at',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically set created_at when creating
        static::creating(function ($model) {
            if (!$model->created_at) {
                $model->created_at = now();
            }
        });
    }

    /**
     * Get the auction this bid belongs to.
     */
    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class, 'auction_id');
    }

    /**
     * Get the user who placed this bid.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if this bid is the current highest bid.
     */
    public function isHighestBid(): bool
    {
        $auction = $this->auction;
        if (!$auction) {
            return false;
        }

        return $auction->last_price == $this->price;
    }

    /**
     * Check if this bid is the winning bid.
     */
    public function isWinningBid(): bool
    {
        $auction = $this->auction;
        if (!$auction || $auction->status !== 'closed') {
            return false;
        }

        return $auction->winner_user_id === $this->user_id 
            && $this->isHighestBid();
    }

    /**
     * Scope to filter bids by auction.
     */
    public function scopeForAuction($query, int $auctionId)
    {
        return $query->where('auction_id', $auctionId);
    }

    /**
     * Scope to filter bids by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to order by price descending (highest first).
     */
    public function scopeHighestFirst($query)
    {
        return $query->orderBy('price', 'desc');
    }

    /**
     * Scope to order by most recent first.
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope to filter only active (non-withdrawn) bids.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter only withdrawn bids.
     */
    public function scopeWithdrawn($query)
    {
        return $query->where('status', 'withdrawn');
    }

    /**
     * Check if this bid can be withdrawn.
     * Bids can only be withdrawn if:
     * - The bid is still active
     * - The auction is still open
     * - This is not the highest bid (or auction allows it)
     */
    public function canBeWithdrawn(): bool
    {
        // Already withdrawn
        if ($this->status === 'withdrawn') {
            return false;
        }

        $auction = $this->auction;
        if (!$auction) {
            return false;
        }

        // Cannot withdraw from closed/cancelled auctions
        if ($auction->status !== 'active') {
            return false;
        }

        // Cannot withdraw if auction has ended
        if ($auction->hasEnded()) {
            return false;
        }

        // Cannot withdraw the highest bid
        if ($this->isHighestBid()) {
            return false;
        }

        return true;
    }

    /**
     * Withdraw this bid.
     */
    public function withdraw(): bool
    {
        if (!$this->canBeWithdrawn()) {
            return false;
        }

        $this->status = 'withdrawn';
        $this->withdrawn_at = now();
        return $this->save();
    }

    /**
     * Get the anonymized bidder label (for privacy).
     */
    public function getAnonymizedBidderLabel(): string
    {
        return 'Bidder #' . $this->user_id;
    }
}
