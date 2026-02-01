<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'country_id',
        'city_id',
        'account_type',
        'profile_image_id',
        'is_verified',
        'seller_verified',
        'seller_verified_at',
        'email_verified_at',
        'otp',
        'otp_expires_at',
    ];

    /**
     * Get the roles associated with the user.
     */
    public function roles()
    {
        return $this->belongsToMany(\App\Models\Role::class, 'user_role');
    }

    /**
     * Get the seller verification requests for the user.
     */
    public function sellerVerificationRequests()
    {
        return $this->hasMany(SellerVerificationRequest::class);
    }

    /**
     * Get the latest seller verification request for the user.
     */
    public function latestSellerVerificationRequest()
    {
        return $this->hasOne(SellerVerificationRequest::class)->latest();
    }

    /**
     * Get the user's favorited ads.
     */
    public function favorites()
    {
        return $this->belongsToMany(Ad::class, 'user_favorites', 'user_id', 'ad_id')->withTimestamps();
    }

    /**
     * Alias for favorites() - Get the user's favorited ads.
     */
    public function favoriteAds()
    {
        return $this->favorites();
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        // Super-admin should have all roles implicitly
        if ($this->roles()->where('name', 'super-admin')->exists()) {
            return true;
        }

        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Check if the user has any of the specified roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        // Super-admin should have all roles implicitly
        if ($this->roles()->where('name', 'super-admin')->exists()) {
            return true;
        }

        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(string $roleName): void
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        if (!$this->roles()->where('role_id', $role->id)->exists()) {
            $this->roles()->attach($role);
        }
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $this->roles()->detach($role);
        }
    }

    /**
     * Check if the user is an admin (admin or super-admin).
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['admin', 'super-admin']);
    }

    /**
     * Check if the user is a dealer or showroom.
     */
    public function isDealerOrShowroom(): bool
    {
        // Check both roles and account_type
        return $this->hasAnyRole(['dealer', 'showroom']) 
            || in_array($this->account_type, ['dealer', 'showroom']);
    }

    /**
     * Get the user's submitted Caishha offers.
     */
    public function caishhaOffers()
    {
        return $this->hasMany(\App\Models\CaishhaOffer::class, 'user_id');
    }

    /**
     * Check if user can submit offer on a Caishha ad.
     * During dealer window: Only dealers and verified sellers can submit.
     * After dealer window: All users (dealers, sellers, individuals) can submit.
     * Seller offers are hidden from ad owner until visibility period expires.
     */
    public function canSubmitCaishhaOffer(\App\Models\CaishhaAd $caishhaAd): bool
    {
        // Cannot submit if ad cannot accept offers
        if (!$caishhaAd->canAcceptOffers()) {
            return false;
        }

        // Check if user already has an offer (allow update, but not duplicate)
        $existingOffer = $caishhaAd->offers()->where('user_id', $this->id)->first();
        // If they already have an offer, they cannot submit a new one (must update existing)
        // This method is for initial submission check; update handled separately
        
        // During dealer window: only dealers and verified sellers
        if ($caishhaAd->isInDealerWindow()) {
            return $this->isDealerOrShowroom() || $this->seller_verified;
        }
        
        // After dealer window (individual window): everyone can submit
        return $caishhaAd->isInIndividualWindow();
    }

    /**
     * Get the user's FindIt requests.
     */
    public function finditRequests()
    {
        return $this->hasMany(\App\Models\FinditRequest::class, 'user_id');
    }

    /**
     * Get reviews created by this user.
     */
    public function reviews()
    {
        return $this->hasMany(\App\Models\Review::class, 'user_id');
    }

    /**
     * Get reviews received by this user (as a seller).
     */
    public function reviewsReceived()
    {
        return $this->hasMany(\App\Models\Review::class, 'seller_id');
    }

    /**
     * Get reports created by this user.
     */
    public function reports()
    {
        return $this->hasMany(\App\Models\Report::class, 'reported_by_user_id');
    }

    /**
     * Get reports received by this user (user being reported).
     */
    public function reportsReceived()
    {
        return $this->morphMany(\App\Models\Report::class, 'target');
    }

    /**
     * Get reports assigned to this user (as a moderator).
     */
    public function assignedReports()
    {
        return $this->hasMany(\App\Models\Report::class, 'assigned_to');
    }

    /**
     * Get the average rating for this user (as a seller).
     */
    public function getAverageRatingAttribute(): float
    {
        return (float) $this->avg_rating;
    }

    /**
     * Get the total reviews count for this user (as a seller).
     */
    public function getTotalReviewsAttribute(): int
    {
        return (int) $this->reviews_count;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'seller_verified_at' => 'datetime',
            'seller_verified' => 'boolean',
            'otp_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
