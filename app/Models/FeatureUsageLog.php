<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureUsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ad_id',
        'feature',
        'credits_source',
        'source_id',
        'credits_used',
        'metadata',
    ];

    protected $casts = [
        'credits_used' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Feature identifiers
     */
    public const FEATURE_FACEBOOK_PUSH = 'facebook_push';
    public const FEATURE_AI_VIDEO = 'ai_video';
    public const FEATURE_AUTO_BG = 'auto_bg';
    public const FEATURE_PIXBLIN = 'pixblin';
    public const FEATURE_CARSEER = 'carseer';

    /**
     * Credit source types
     */
    public const SOURCE_PACKAGE = 'package';
    public const SOURCE_UNIQUE_AD_TYPE = 'unique_ad_type';

    /**
     * All trackable features
     */
    public const ALL_FEATURES = [
        self::FEATURE_FACEBOOK_PUSH,
        self::FEATURE_AI_VIDEO,
        self::FEATURE_AUTO_BG,
        self::FEATURE_PIXBLIN,
        self::FEATURE_CARSEER,
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    // ========================================
    // QUERY HELPERS
    // ========================================

    /**
     * Get total credits used by a user for a specific feature from a source.
     */
    public static function getUsedCredits(int $userId, string $feature, string $source, int $sourceId): int
    {
        return static::where('user_id', $userId)
            ->where('feature', $feature)
            ->where('credits_source', $source)
            ->where('source_id', $sourceId)
            ->sum('credits_used');
    }

    /**
     * Get total credits used by a user for a feature from ANY source.
     */
    public static function getTotalUsedCredits(int $userId, string $feature): int
    {
        return static::where('user_id', $userId)
            ->where('feature', $feature)
            ->sum('credits_used');
    }

    /**
     * Get usage for a specific ad.
     */
    public static function getAdUsage(int $adId, string $feature): int
    {
        return static::where('ad_id', $adId)
            ->where('feature', $feature)
            ->sum('credits_used');
    }
}
