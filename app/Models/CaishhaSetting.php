<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CaishhaSetting extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'caishha_settings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Cache duration for settings (in minutes).
     */
    private const CACHE_DURATION = 60;

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = 'caishha_setting_' . $key;
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }

            return static::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value, string $type = 'string', string $description = null): bool
    {
        $stringValue = static::valueToString($value, $type);
        
        $result = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'type' => $type,
                'description' => $description,
            ]
        );

        // Clear cache
        $cacheKey = 'caishha_setting_' . $key;
        Cache::forget($cacheKey);

        return (bool) $result;
    }

    /**
     * Get all settings as an array.
     */
    public static function getAllSettings(): array
    {
        return Cache::remember('caishha_settings_all', self::CACHE_DURATION, function () {
            $settings = static::query()->get();
            $result = [];

            foreach ($settings as $setting) {
                $result[$setting->key] = [
                    'value' => static::castValue($setting->value, $setting->type),
                    'type' => $setting->type,
                    'description' => $setting->description,
                ];
            }

            return $result;
        });
    }

    /**
     * Clear all settings cache.
     */
    public static function clearCache(): void
    {
        Cache::forget('caishha_settings_all');
        
        // Clear individual setting caches
        $keys = static::pluck('key');
        foreach ($keys as $key) {
            Cache::forget('caishha_setting_' . $key);
        }
    }

    /**
     * Cast a string value to its proper type.
     */
    private static function castValue(string $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'float' => (float) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Convert a value to string for storage.
     */
    private static function valueToString(mixed $value, string $type): string
    {
        return match ($type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    /**
     * Get default dealer window period in seconds.
     */
    public static function getDefaultDealerWindowSeconds(): int
    {
        return static::get('default_dealer_window_seconds', 129600);
    }

    /**
     * Get default visibility period in seconds.
     */
    public static function getDefaultVisibilityPeriodSeconds(): int
    {
        return static::get('default_visibility_period_seconds', 129600);
    }

    /**
     * Get minimum dealer window seconds.
     */
    public static function getMinDealerWindowSeconds(): int
    {
        return static::get('min_dealer_window_seconds', 3600);
    }

    /**
     * Get maximum dealer window seconds.
     */
    public static function getMaxDealerWindowSeconds(): int
    {
        return static::get('max_dealer_window_seconds', 604800);
    }

    /**
     * Get minimum visibility period seconds.
     */
    public static function getMinVisibilityPeriodSeconds(): int
    {
        return static::get('min_visibility_period_seconds', 0);
    }

    /**
     * Get maximum visibility period seconds.
     */
    public static function getMaxVisibilityPeriodSeconds(): int
    {
        return static::get('max_visibility_period_seconds', 604800);
    }

    /**
     * Validate if a dealer window period is within allowed range.
     */
    public static function isValidDealerWindowPeriod(int $seconds): bool
    {
        $min = static::getMinDealerWindowSeconds();
        $max = static::getMaxDealerWindowSeconds();
        return $seconds >= $min && $seconds <= $max;
    }

    /**
     * Validate if a visibility period is within allowed range.
     */
    public static function isValidVisibilityPeriod(int $seconds): bool
    {
        $min = static::getMinVisibilityPeriodSeconds();
        $max = static::getMaxVisibilityPeriodSeconds();
        return $seconds >= $min && $seconds <= $max;
    }

    /**
     * Boot method to clear cache when settings are updated.
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            static::clearCache();
        });

        static::deleted(function () {
            static::clearCache();
        });
    }
}