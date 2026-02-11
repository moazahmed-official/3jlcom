<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\FeatureUsageLog;
use App\Models\PackageFeature;
use App\Models\UniqueAd;
use App\Models\UniqueAdTypeDefinition;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FeatureUsageService
{
    /**
     * Check if a user has credits remaining for a feature.
     * Resolves from the correct source: package features (paid) or unique ad type (free).
     *
     * @return array ['has_credits' => bool, 'remaining' => int, 'total' => int, 'source' => string, 'source_id' => int]
     */
    public function checkCredits(User $user, string $feature, ?int $adId = null): array
    {
        // Admins always have unlimited credits
        if ($user->isAdmin()) {
            return [
                'has_credits' => true,
                'remaining' => PHP_INT_MAX,
                'total' => PHP_INT_MAX,
                'source' => 'admin',
                'source_id' => 0,
            ];
        }

        $activePackage = $user->activePackage;

        if (!$activePackage) {
            return $this->noCreditsResult();
        }

        // PAID PLAN: Credits come from PackageFeature
        if (!$activePackage->isFree()) {
            return $this->checkPackageCredits($user, $activePackage, $feature);
        }

        // FREE PLAN: Credits come from the unique ad type definition (if ad is a unique ad)
        if ($adId) {
            return $this->checkUniqueAdTypeCredits($user, $adId, $feature);
        }

        return $this->noCreditsResult();
    }

    /**
     * Consume credits for a feature usage (log it and verify availability).
     *
     * @param int $credits Number of credits to consume (default 1)
     * @return array ['success' => bool, 'reason' => string|null, 'remaining' => int, 'log_id' => int|null]
     */
    public function consumeCredits(User $user, string $feature, ?int $adId = null, int $credits = 1, array $metadata = []): array
    {
        $availability = $this->checkCredits($user, $feature, $adId);

        if (!$availability['has_credits'] || $availability['remaining'] < $credits) {
            return [
                'success' => false,
                'reason' => "Insufficient {$feature} credits. Remaining: {$availability['remaining']}, required: {$credits}.",
                'remaining' => $availability['remaining'],
                'log_id' => null,
            ];
        }

        $log = DB::transaction(function () use ($user, $feature, $adId, $credits, $availability, $metadata) {
            return FeatureUsageLog::create([
                'user_id' => $user->id,
                'ad_id' => $adId,
                'feature' => $feature,
                'credits_source' => $availability['source'],
                'source_id' => $availability['source_id'],
                'credits_used' => $credits,
                'metadata' => $metadata,
            ]);
        });

        return [
            'success' => true,
            'reason' => null,
            'remaining' => $availability['remaining'] - $credits,
            'log_id' => $log->id,
        ];
    }

    /**
     * Get remaining credits for a user across all features.
     */
    public function getAllCredits(User $user): array
    {
        $credits = [];

        foreach (FeatureUsageLog::ALL_FEATURES as $feature) {
            $credits[$feature] = $this->checkCredits($user, $feature);
        }

        return $credits;
    }

    /**
     * Get usage history for a user with optional filters.
     */
    public function getUsageHistory(User $user, ?string $feature = null, ?int $adId = null, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        $query = FeatureUsageLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($feature) {
            $query->where('feature', $feature);
        }

        if ($adId) {
            $query->where('ad_id', $adId);
        }

        return $query->limit($limit)->get();
    }

    // ========================================
    // PRIVATE METHODS
    // ========================================

    /**
     * Check credits from package features (PAID plan flow).
     */
    private function checkPackageCredits(User $user, $package, string $feature): array
    {
        $packageFeatures = $package->packageFeatures;

        if (!$packageFeatures) {
            return $this->noCreditsResult();
        }

        $totalCredits = $packageFeatures->getFeatureCredits($feature);

        if ($totalCredits <= 0) {
            return $this->noCreditsResult();
        }

        $usedCredits = FeatureUsageLog::getUsedCredits(
            $user->id,
            $feature,
            FeatureUsageLog::SOURCE_PACKAGE,
            $packageFeatures->id
        );

        $remaining = $totalCredits - $usedCredits;

        return [
            'has_credits' => $remaining > 0,
            'remaining' => max(0, $remaining),
            'total' => $totalCredits,
            'source' => FeatureUsageLog::SOURCE_PACKAGE,
            'source_id' => $packageFeatures->id,
        ];
    }

    /**
     * Check credits from unique ad type definition (FREE plan flow).
     */
    private function checkUniqueAdTypeCredits(User $user, int $adId, string $feature): array
    {
        // Find the unique ad associated with this ad
        $uniqueAd = UniqueAd::where('ad_id', $adId)->first();

        if (!$uniqueAd || !$uniqueAd->unique_ad_type_id) {
            return $this->noCreditsResult();
        }

        $typeDef = $uniqueAd->typeDefinition;

        if (!$typeDef) {
            return $this->noCreditsResult();
        }

        $totalCredits = $this->getTypeDefinitionCredits($typeDef, $feature);

        if ($totalCredits <= 0) {
            return $this->noCreditsResult();
        }

        $usedCredits = FeatureUsageLog::getUsedCredits(
            $user->id,
            $feature,
            FeatureUsageLog::SOURCE_UNIQUE_AD_TYPE,
            $typeDef->id
        );

        $remaining = $totalCredits - $usedCredits;

        return [
            'has_credits' => $remaining > 0,
            'remaining' => max(0, $remaining),
            'total' => $totalCredits,
            'source' => FeatureUsageLog::SOURCE_UNIQUE_AD_TYPE,
            'source_id' => $typeDef->id,
        ];
    }

    /**
     * Map feature name to UniqueAdTypeDefinition credit column.
     */
    private function getTypeDefinitionCredits(UniqueAdTypeDefinition $typeDef, string $feature): int
    {
        $map = [
            FeatureUsageLog::FEATURE_FACEBOOK_PUSH => $typeDef->facebook_push_enabled ? 1 : 0,
            FeatureUsageLog::FEATURE_CARSEER => $typeDef->carseer_api_credits ?? 0,
            FeatureUsageLog::FEATURE_AUTO_BG => $typeDef->auto_bg_credits ?? 0,
            FeatureUsageLog::FEATURE_PIXBLIN => $typeDef->pixblin_credits ?? 0,
            FeatureUsageLog::FEATURE_AI_VIDEO => 0, // Not on UniqueAdTypeDefinition, only on packages
        ];

        return $map[$feature] ?? 0;
    }

    /**
     * Default no-credits result.
     */
    private function noCreditsResult(): array
    {
        return [
            'has_credits' => false,
            'remaining' => 0,
            'total' => 0,
            'source' => 'none',
            'source_id' => 0,
        ];
    }
}
