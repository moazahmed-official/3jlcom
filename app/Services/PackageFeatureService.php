<?php

namespace App\Services;

use App\Models\User;
use App\Models\Package;
use App\Models\PackageFeature;
use App\Models\UserPackage;
use App\Models\Ad;
use Illuminate\Support\Facades\DB;

class PackageFeatureService
{
    /**
     * Validate if a user can create an ad of a specific type.
     *
     * @param User $user
     * @param string $adType
     * @return array ['allowed' => bool, 'reason' => string|null, 'remaining' => int|null]
     */
    public function validateAdCreation(User $user, string $adType): array
    {
        // Admins bypass all restrictions
        if ($user->isAdmin()) {
            return [
                'allowed' => true,
                'reason' => null,
                'remaining' => null,
            ];
        }

        // Check if ad type is allowed
        if (!$user->canPublishAdType($adType)) {
            return [
                'allowed' => false,
                'reason' => "Your package does not allow {$adType} ads. Please upgrade your package.",
                'remaining' => 0,
            ];
        }

        // Check if user has reached their limit
        $remaining = $user->getRemainingAdsForType($adType);
        
        if ($remaining !== null && $remaining <= 0) {
            return [
                'allowed' => false,
                'reason' => "You have reached your {$adType} ads limit. Please upgrade your package or wait for existing ads to expire.",
                'remaining' => 0,
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'remaining' => $remaining,
        ];
    }

    /**
     * Validate ad feature usage (banner, background color, facebook push, etc.)
     *
     * @param User $user
     * @param array $requestedFeatures
     * @return array ['allowed' => bool, 'denied_features' => array, 'reason' => string|null]
     */
    public function validateAdFeatures(User $user, array $requestedFeatures): array
    {
        $deniedFeatures = [];
        
        // Check each requested feature
        $featureChecks = [
            'is_pushed_facebook' => ['method' => 'canPushToFacebook', 'label' => 'Facebook push'],
            'is_auto_republished' => ['method' => 'canAutoRepublish', 'label' => 'auto-republish'],
            'banner_image_id' => ['method' => 'canUseBanner', 'label' => 'banner'],
            'banner_color' => ['method' => 'canUseBackgroundColor', 'label' => 'background color'],
            'is_featured' => ['method' => 'canFeatureAds', 'label' => 'featuring'],
        ];

        foreach ($featureChecks as $feature => $check) {
            if (isset($requestedFeatures[$feature]) && $requestedFeatures[$feature]) {
                $method = $check['method'];
                if (!$user->{$method}()) {
                    $deniedFeatures[$feature] = $check['label'];
                }
            }
        }

        if (!empty($deniedFeatures)) {
            return [
                'allowed' => false,
                'denied_features' => $deniedFeatures,
                'reason' => 'Your package does not include: ' . implode(', ', $deniedFeatures),
            ];
        }

        return [
            'allowed' => true,
            'denied_features' => [],
            'reason' => null,
        ];
    }

    /**
     * Validate media limits for an ad.
     *
     * @param User $user
     * @param int $imageCount
     * @param int $videoCount
     * @return array ['allowed' => bool, 'reason' => string|null]
     */
    public function validateMediaLimits(User $user, int $imageCount, int $videoCount): array
    {
        $imagesLimit = $user->getImagesPerAdLimit();
        $videosLimit = $user->getVideosPerAdLimit();

        $errors = [];

        if ($imageCount > $imagesLimit) {
            $errors[] = "Maximum {$imagesLimit} images allowed per ad (you requested {$imageCount})";
        }

        if ($videoCount > $videosLimit) {
            $errors[] = "Maximum {$videosLimit} videos allowed per ad (you requested {$videoCount})";
        }

        if (!empty($errors)) {
            return [
                'allowed' => false,
                'reason' => implode('. ', $errors),
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
        ];
    }

    /**
     * Validate ad duration.
     *
     * @param User $user
     * @param int $requestedDays
     * @return array ['allowed' => bool, 'reason' => string|null, 'max_allowed' => int]
     */
    public function validateAdDuration(User $user, int $requestedDays): array
    {
        $maxDays = $user->getMaxAdDuration();

        if ($requestedDays > $maxDays) {
            return [
                'allowed' => false,
                'reason' => "Maximum ad duration is {$maxDays} days. You requested {$requestedDays} days.",
                'max_allowed' => $maxDays,
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'max_allowed' => $maxDays,
        ];
    }

    /**
     * Apply package features when a package is assigned to a user.
     *
     * @param UserPackage $userPackage
     * @return void
     */
    public function applyPackageFeatures(UserPackage $userPackage): void
    {
        $user = $userPackage->user;
        $package = $userPackage->package;
        $features = $package->packageFeatures;

        if (!$features || !$user) {
            return;
        }

        DB::transaction(function () use ($user, $features) {
            // Grant seller status
            if ($features->grants_seller_status) {
                $user->assignRole('seller');
                
                // Update account type if it's still 'individual'
                if ($user->account_type === 'individual' || !$user->account_type) {
                    $user->account_type = 'dealer';
                }
                
                // Auto-verify seller
                if ($features->auto_verify_seller && !$user->seller_verified) {
                    $user->seller_verified = true;
                    $user->seller_verified_at = now();
                }
            }

            // Grant marketer status
            if ($features->grants_marketer_status) {
                $user->assignRole('marketer');
            }

            // Grant verified badge
            if ($features->grants_verified_badge && !$user->is_verified) {
                $user->is_verified = true;
            }

            $user->save();
        });
    }

    /**
     * Revoke package features when a package expires or is removed.
     * 
     * Note: This is a soft revoke - we only remove features if the user
     * doesn't have another active package that provides them.
     *
     * @param UserPackage $userPackage
     * @return void
     */
    public function revokePackageFeatures(UserPackage $userPackage): void
    {
        $user = $userPackage->user;
        $revokedPackage = $userPackage->package;
        $revokedFeatures = $revokedPackage->packageFeatures;

        if (!$revokedFeatures || !$user) {
            return;
        }

        // Check if user has any other active packages that provide the same features
        $otherActivePackages = UserPackage::where('user_id', $user->id)
            ->where('id', '!=', $userPackage->id)
            ->valid()
            ->with('package.packageFeatures')
            ->get();

        $hasOtherSellerPackage = false;
        $hasOtherMarketerPackage = false;
        $hasOtherVerifiedBadge = false;

        foreach ($otherActivePackages as $otherUserPackage) {
            $otherFeatures = $otherUserPackage->package->packageFeatures;
            if ($otherFeatures) {
                if ($otherFeatures->grants_seller_status) $hasOtherSellerPackage = true;
                if ($otherFeatures->grants_marketer_status) $hasOtherMarketerPackage = true;
                if ($otherFeatures->grants_verified_badge) $hasOtherVerifiedBadge = true;
            }
        }

        DB::transaction(function () use ($user, $revokedFeatures, $hasOtherSellerPackage, $hasOtherMarketerPackage, $hasOtherVerifiedBadge) {
            // Only revoke seller if no other package provides it
            if ($revokedFeatures->grants_seller_status && !$hasOtherSellerPackage) {
                $user->removeRole('seller');
                // We don't revert account_type as it may have been set independently
            }

            // Only revoke marketer if no other package provides it
            if ($revokedFeatures->grants_marketer_status && !$hasOtherMarketerPackage) {
                $user->removeRole('marketer');
            }

            // We typically don't revoke verified badge once granted
            // But if policy requires, uncomment below:
            // if ($revokedFeatures->grants_verified_badge && !$hasOtherVerifiedBadge) {
            //     $user->is_verified = false;
            // }

            $user->save();
        });
    }

    /**
     * Get usage statistics for a user's package.
     *
     * @param User $user
     * @return array
     */
    public function getUsageStatistics(User $user): array
    {
        $package = $user->getCurrentPackage();
        
        if (!$package) {
            return [
                'has_package' => false,
                'usage' => null,
            ];
        }

        $features = $package->packageFeatures;
        
        $adTypes = PackageFeature::AD_TYPES;
        $usage = [];

        foreach ($adTypes as $type) {
            $allowed = $package->isAdTypeAllowed($type);
            $limit = $package->getAdTypeLimit($type);
            $used = $user->countActiveAdsByType($type);
            
            $usage[$type] = [
                'allowed' => $allowed,
                'limit' => $limit,
                'used' => $used,
                'remaining' => $limit !== null ? max(0, $limit - $used) : null,
                'unlimited' => $allowed && $limit === null,
            ];
        }

        // Featured ads usage
        $featuredLimit = $features?->featured_ads_limit;
        $featuredUsed = Ad::where('user_id', $user->id)
            ->whereHas('uniqueAd', function ($query) {
                $query->where('is_featured', true);
            })
            ->whereIn('status', ['draft', 'pending', 'published'])
            ->count();

        return [
            'has_package' => true,
            'package' => [
                'id' => $package->id,
                'name' => $package->name,
            ],
            'usage' => [
                'ad_types' => $usage,
                'featured_ads' => [
                    'allowed' => $features?->can_feature_ads ?? false,
                    'limit' => $featuredLimit,
                    'used' => $featuredUsed,
                    'remaining' => $featuredLimit !== null ? max(0, $featuredLimit - $featuredUsed) : null,
                    'unlimited' => ($features?->can_feature_ads ?? false) && $featuredLimit === null,
                ],
            ],
        ];
    }

    /**
     * Check if user can perform bulk upload.
     *
     * @param User $user
     * @param int $itemCount
     * @return array ['allowed' => bool, 'reason' => string|null, 'max_allowed' => int|null]
     */
    public function validateBulkUpload(User $user, int $itemCount): array
    {
        if (!$user->canBulkUpload()) {
            return [
                'allowed' => false,
                'reason' => 'Your package does not include bulk upload feature.',
                'max_allowed' => 0,
            ];
        }

        $features = $user->getCurrentPackageFeatures();
        $limit = $features?->bulk_upload_limit;

        if ($limit !== null && $itemCount > $limit) {
            return [
                'allowed' => false,
                'reason' => "Bulk upload limit is {$limit} items per upload. You requested {$itemCount} items.",
                'max_allowed' => $limit,
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'max_allowed' => $limit,
        ];
    }

    /**
     * Get the effective default ad duration for a user.
     *
     * @param User $user
     * @return int
     */
    public function getEffectiveAdDuration(User $user): int
    {
        return $user->getDefaultAdDuration();
    }

    /**
     * Determine allowed features for an ad based on user's package.
     * This is useful for frontend to know what fields to show/enable.
     *
     * @param User $user
     * @return array
     */
    public function getAllowedAdFeatures(User $user): array
    {
        $features = $user->getCurrentPackageFeatures();
        
        return [
            'facebook_push' => $user->canPushToFacebook(),
            'auto_republish' => $user->canAutoRepublish(),
            'banner' => $user->canUseBanner(),
            'background_color' => $user->canUseBackgroundColor(),
            'featuring' => $user->canFeatureAds(),
            'images_limit' => $user->getImagesPerAdLimit(),
            'videos_limit' => $user->getVideosPerAdLimit(),
            'default_duration' => $user->getDefaultAdDuration(),
            'max_duration' => $user->getMaxAdDuration(),
            'show_contact_immediately' => $features?->show_contact_immediately ?? false,
        ];
    }
}
