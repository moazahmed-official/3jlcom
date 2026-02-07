<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CompanySetting extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'company_settings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'key',
        'value',
        'is_active',
        'type',
        'description',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Valid setting types.
     */
    public const TYPES = [
        'contact',
        'social_media',
        'app_link',
    ];

    /**
     * Valid setting keys.
     */
    public const VALID_KEYS = [
        // Contacts
        'phone',
        'email',
        'location',
        // Social Media
        'facebook_link',
        'instagram_link',
        'twitter_link',
        'youtube_link',
        'telegram_link',
        'whatsapp_link',
        'tiktok_link',
        // App Links
        'android_app_link',
        'ios_app_link',
    ];

    /**
     * Cache duration in minutes.
     */
    private const CACHE_DURATION = 60;

    /**
     * Get a setting value by key (cached).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("company_setting_{$key}", self::CACHE_DURATION, function () use ($key) {
            return static::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        return $setting->value;
    }

    /**
     * Check if a setting is active.
     */
    public static function isActive(string $key): bool
    {
        $setting = Cache::remember("company_setting_{$key}", self::CACHE_DURATION, function () use ($key) {
            return static::where('key', $key)->first();
        });

        return $setting ? $setting->is_active : false;
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, ?string $value, ?bool $isActive = null): bool
    {
        $data = ['value' => $value];

        if ($isActive !== null) {
            $data['is_active'] = $isActive;
        }

        $result = static::where('key', $key)->update($data);

        // Clear cache
        static::clearCache($key);

        return (bool) $result;
    }

    /**
     * Get all settings (cached).
     */
    public static function getAllSettings(): array
    {
        return Cache::remember('company_settings_all', self::CACHE_DURATION, function () {
            return static::all()->groupBy('type')->map(function ($group) {
                return $group->map(function ($setting) {
                    return [
                        'key' => $setting->key,
                        'value' => $setting->value,
                        'is_active' => $setting->is_active,
                        'description' => $setting->description,
                    ];
                })->keyBy('key');
            })->toArray();
        });
    }

    /**
     * Get all active settings (for public API).
     */
    public static function getActiveSettings(): array
    {
        return Cache::remember('company_settings_active', self::CACHE_DURATION, function () {
            return static::where('is_active', true)->get()->groupBy('type')->map(function ($group) {
                return $group->map(function ($setting) {
                    return [
                        'key' => $setting->key,
                        'value' => $setting->value,
                    ];
                })->keyBy('key');
            })->toArray();
        });
    }

    /**
     * Get settings by type.
     */
    public static function getByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('type', $type)->get();
    }

    /**
     * Clear cache for a specific key or all settings.
     */
    public static function clearCache(?string $key = null): void
    {
        if ($key) {
            Cache::forget("company_setting_{$key}");
        }
        Cache::forget('company_settings_all');
        Cache::forget('company_settings_active');
    }

    /**
     * Check if a key is valid.
     */
    public static function isValidKey(string $key): bool
    {
        return in_array($key, self::VALID_KEYS);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter active settings.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
