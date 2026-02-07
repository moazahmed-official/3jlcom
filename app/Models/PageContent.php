<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PageContent extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'page_contents';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'page_key',
        'title_en',
        'title_ar',
        'body_en',
        'body_ar',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Valid page keys.
     */
    public const VALID_PAGES = [
        'about_us',
        'privacy_policy',
        'terms_conditions',
    ];

    /**
     * Cache duration in minutes.
     */
    private const CACHE_DURATION = 60;

    /**
     * Get a page by its key (cached).
     */
    public static function getByKey(string $pageKey): ?self
    {
        return Cache::remember("page_content_{$pageKey}", self::CACHE_DURATION, function () use ($pageKey) {
            return static::where('page_key', $pageKey)->first();
        });
    }

    /**
     * Get all pages (cached).
     */
    public static function getAllPages(): array
    {
        return Cache::remember('page_contents_all', self::CACHE_DURATION, function () {
            return static::all()->keyBy('page_key')->toArray();
        });
    }

    /**
     * Clear cache for a specific page or all pages.
     */
    public static function clearCache(?string $pageKey = null): void
    {
        if ($pageKey) {
            Cache::forget("page_content_{$pageKey}");
        }
        Cache::forget('page_contents_all');
    }

    /**
     * Check if a page key is valid.
     */
    public static function isValidPageKey(string $key): bool
    {
        return in_array($key, self::VALID_PAGES);
    }
}
