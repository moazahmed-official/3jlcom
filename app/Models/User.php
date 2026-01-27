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
            'otp_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
