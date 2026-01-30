<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaishhaAd extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'caishha_ads';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'ad_id',
        'offers_window_period',
        'offers_count',
        'sellers_visibility_period',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'offers_window_period' => 'integer',
        'offers_count' => 'integer',
        'sellers_visibility_period' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the base ad that this Caishha ad belongs to.
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class, 'ad_id');
    }

    /**
     * Get all offers for this Caishha ad.
     */
    public function offers(): HasMany
    {
        return $this->hasMany(CaishhaOffer::class, 'ad_id', 'ad_id');
    }

    /**
     * Get pending offers for this Caishha ad.
     */
    public function pendingOffers(): HasMany
    {
        return $this->offers()->pending();
    }

    /**
     * Get the accepted offer for this Caishha ad.
     */
    public function acceptedOffer()
    {
        return $this->offers()->accepted()->first();
    }

    /**
     * Get the effective dealer window period in seconds.
     */
    public function getDealerWindowPeriod(): int
    {
        return $this->offers_window_period ?? CaishhaSetting::getDefaultDealerWindowSeconds();
    }

    /**
     * Get the effective visibility period in seconds.
     */
    public function getVisibilityPeriod(): int
    {
        return $this->sellers_visibility_period ?? CaishhaSetting::getDefaultVisibilityPeriodSeconds();
    }

    /**
     * Get the dealer window end time.
     */
    public function getDealerWindowEndsAt(): ?\DateTime
    {
        $ad = $this->ad;
        if (!$ad || !$ad->published_at) {
            return null;
        }

        return $ad->published_at->addSeconds($this->getDealerWindowPeriod());
    }

    /**
     * Get the visibility period end time.
     */
    public function getVisibilityPeriodEndsAt(): ?\DateTime
    {
        $ad = $this->ad;
        if (!$ad || !$ad->published_at) {
            return null;
        }

        return $ad->published_at->addSeconds($this->getVisibilityPeriod());
    }

    /**
     * Check if currently in dealer-exclusive window.
     */
    public function isInDealerWindow(): bool
    {
        $endsAt = $this->getDealerWindowEndsAt();
        if (!$endsAt) {
            return false;
        }

        return now()->lessThan($endsAt);
    }

    /**
     * Check if currently in individual window (after dealer window).
     */
    public function isInIndividualWindow(): bool
    {
        $ad = $this->ad;
        if (!$ad || !$ad->published_at) {
            return false;
        }

        $dealerWindowEndsAt = $this->getDealerWindowEndsAt();
        if (!$dealerWindowEndsAt) {
            return false;
        }

        // After dealer window and ad is still published/not expired
        return now()->greaterThanOrEqualTo($dealerWindowEndsAt) 
            && $ad->status === 'published';
    }

    /**
     * Check if offers are visible to seller (visibility period expired).
     */
    public function areOffersVisibleToSeller(): bool
    {
        $endsAt = $this->getVisibilityPeriodEndsAt();
        if (!$endsAt) {
            return false;
        }

        return now()->greaterThanOrEqualTo($endsAt);
    }

    /**
     * Check if offers can still be submitted (ad is published and not sold).
     */
    public function canAcceptOffers(): bool
    {
        $ad = $this->ad;
        if (!$ad) {
            return false;
        }

        // Ad must be published and not have an accepted offer
        return $ad->status === 'published' && !$this->acceptedOffer();
    }

    /**
     * Increment the offers count.
     */
    public function incrementOffersCount(): void
    {
        $this->increment('offers_count');
    }

    /**
     * Decrement the offers count.
     */
    public function decrementOffersCount(): void
    {
        $this->decrement('offers_count');
    }
}
