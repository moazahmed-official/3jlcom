<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\UniqueAd;
use App\Models\UniqueAdTypeDefinition;
use App\Models\User;
use App\Models\CaishhaAd;

class UniqueAdTypeService
{
    /**
     * Validate if user can create an ad of a specific unique type.
     *
     * @throws \Exception
     */
    public function validateUserCanCreateType(User $user, UniqueAdTypeDefinition $type): bool
    {
        // Admins can always create any type
        if ($user->role === 'admin') {
            return true;
        }

        // Check if type is active
        if (!$type->active) {
            throw new \Exception('This ad type is not currently available');
        }

        // Get user's active package
        $activePackage = $user->activePackage;

        if (!$activePackage) {
            throw new \Exception('You need an active package to create unique ads');
        }

        // Check if package allows this specific unique ad type
        if (!$activePackage->allowsUniqueAdType($type->id)) {
            throw new \Exception('Your package does not allow this ad type. Please upgrade your package.');
        }

        // Check remaining ads quota for this type
        $remaining = $activePackage->getRemainingAdsForUniqueType($user, $type->id);

        if ($remaining !== null && $remaining <= 0) {
            throw new \Exception('You have reached the limit for this ad type in your package');
        }

        return true;
    }

    /**
     * Apply type features to a unique ad during creation or upgrade.
     */
    public function applyTypeFeatures(UniqueAd $uniqueAd, UniqueAdTypeDefinition $type): void
    {
        // Enable auto-republish if type allows
        if ($type->auto_republish_enabled) {
            $uniqueAd->is_auto_republished = true;
        }

        // Mark as featured if this is a high-priority type
        if ($type->priority < 300) {
            $uniqueAd->is_featured = true;
            $uniqueAd->featured_at = now();
        }

        $uniqueAd->save();
    }

    /**
     * Validate requested features against type definition.
     */
    public function validateRequestedFeatures(array $requestData, UniqueAdTypeDefinition $type): array
    {
        $errors = [];

        // Validate frame features
        if (!empty($requestData['banner_color']) && !$type->allows_colored_frame) {
            $errors['banner_color'] = 'Colored frame is not allowed for this ad type';
        }

        if (!empty($requestData['banner_image_id']) && !$type->allows_image_frame) {
            $errors['banner_image_id'] = 'Image frame is not allowed for this ad type';
        }

        // Validate Caishha feature
        if (!empty($requestData['enable_caishha_feature']) && !$type->caishha_feature_enabled) {
            $errors['enable_caishha_feature'] = 'Caishha feature is not available for this ad type';
        }

        // Validate Facebook push (if explicitly requested in data)
        if (!empty($requestData['push_to_facebook']) && !$type->facebook_push_enabled) {
            $errors['push_to_facebook'] = 'Facebook push is not available for this ad type';
        }

        return $errors;
    }

    /**
     * Validate media counts against type limits.
     */
    public function validateMediaCounts(array $mediaIds, UniqueAdTypeDefinition $type): array
    {
        $errors = [];

        // In real implementation, you'd query the media table to separate images from videos
        // For now, this is a placeholder structure
        $imageCount = count(array_filter($mediaIds, function ($id) {
            // This should check media type from database
            return true; // Placeholder
        }));

        $videoCount = count($mediaIds) - $imageCount;

        if ($imageCount > $type->max_images) {
            $errors['media'] = "Maximum {$type->max_images} images allowed for this ad type";
        }

        if ($videoCount > $type->max_videos) {
            $errors['media'] = "Maximum {$type->max_videos} videos allowed for this ad type";
        }

        return $errors;
    }

    /**
     * Create Caishha feature data for unique ad if enabled.
     */
    public function enableCaishhaFeature(Ad $ad, UniqueAd $uniqueAd): void
    {
        // Get default Caishha settings
        $caishhaSettings = \App\Models\CaishhaSetting::getSettings();

        // Create caishha_ads record
        CaishhaAd::create([
            'ad_id' => $ad->id,
            'offers_window_period' => $caishhaSettings->default_offers_window_period ?? 48,
            'sellers_visibility_period' => $caishhaSettings->default_sellers_visibility_period ?? 24,
            'offers_count' => 0,
        ]);

        // Mark unique ad as using caishha feature
        $uniqueAd->applies_caishha_feature = true;
        $uniqueAd->save();
    }

    /**
     * Get available unique ad types for a user based on their package.
     */
    public function getAvailableTypesForUser(User $user): array
    {
        // Admins can see all types
        if ($user->role === 'admin') {
            return UniqueAdTypeDefinition::active()
                ->byPriority()
                ->get()
                ->toArray();
        }

        $activePackage = $user->activePackage;

        if (!$activePackage) {
            return [];
        }

        // Get specific unique ad types allowed by package
        $specificTypes = $activePackage->uniqueAdTypes()
            ->where('active', true)
            ->byPriority()
            ->get();

        if ($specificTypes->isNotEmpty()) {
            return $specificTypes->toArray();
        }

        // If no specific types but generic unique allowed, return all active types
        if ($activePackage->isAdTypeAllowed('unique')) {
            return UniqueAdTypeDefinition::active()
                ->byPriority()
                ->get()
                ->toArray();
        }

        return [];
    }
}
