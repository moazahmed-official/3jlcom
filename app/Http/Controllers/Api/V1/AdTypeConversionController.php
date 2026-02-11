<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Traits\LogsAudit;
use App\Models\Ad;
use App\Models\AdTypeConversion;
use App\Models\NormalAd;
use App\Models\UniqueAd;
use App\Models\UniqueAdTypeDefinition;
use App\Models\PackageFeature;
use App\Services\PackageFeatureService;
use App\Services\UniqueAdTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdTypeConversionController extends BaseApiController
{
    use LogsAudit;

    protected PackageFeatureService $packageFeatureService;
    protected UniqueAdTypeService $typeService;

    public function __construct(PackageFeatureService $packageFeatureService, UniqueAdTypeService $typeService)
    {
        $this->packageFeatureService = $packageFeatureService;
        $this->typeService = $typeService;
    }

    /**
     * Convert an ad from one type to another.
     *
     * Business rules:
     * - Both the source type counter and destination type counter are deducted.
     * - The ad's type column is updated, and the appropriate sub-table record is created/removed.
     * - Only available for PAID plans (free plans use upgrade requests).
     *
     * POST /api/v1/ads/{ad}/convert
     */
    public function convert(Request $request, Ad $ad): JsonResponse
    {
        $request->validate([
            'to_type' => 'required|string|in:normal,unique,caishha',
            'unique_ad_type_id' => 'nullable|integer|exists:unique_ad_type_definitions,id',
        ]);

        $user = auth()->user();

        // Verify ownership
        if ($ad->user_id !== $user->id && !$user->isAdmin()) {
            return $this->error(403, 'You do not own this ad');
        }

        $fromType = $ad->type;
        $toType = $request->to_type;

        // Cannot convert to same type
        if ($fromType === $toType) {
            return $this->error(422, 'The ad is already of this type', [
                'to_type' => ['Cannot convert to the same type'],
            ]);
        }

        // Only paid plan users can convert ads
        $activePackage = $user->activePackage;
        if (!$activePackage) {
            return $this->error(403, 'You need an active package to convert ads');
        }

        if ($activePackage->isFree()) {
            return $this->error(403, 'Free plan users must use the upgrade request system. Ad conversion is only available for paid plans.');
        }

        // Check if destination type is allowed by package
        $destValidation = $this->packageFeatureService->validateAdCreation($user, $toType);
        if (!$destValidation['allowed']) {
            return $this->error(403, $destValidation['reason']);
        }

        // If converting to unique, validate unique ad type
        $uniqueAdTypeId = null;
        if ($toType === 'unique') {
            if (!$request->unique_ad_type_id) {
                return $this->error(422, 'unique_ad_type_id is required when converting to unique type', [
                    'unique_ad_type_id' => ['A unique ad type must be specified'],
                ]);
            }

            $typeDef = UniqueAdTypeDefinition::find($request->unique_ad_type_id);
            if (!$typeDef || !$typeDef->active) {
                return $this->error(404, 'The specified unique ad type is not available');
            }

            // Validate user can use this specific type
            try {
                $this->typeService->validateUserCanCreateType($user, $typeDef);
            } catch (\Exception $e) {
                return $this->error(403, $e->getMessage());
            }

            $uniqueAdTypeId = $typeDef->id;
        }

        // Perform the conversion
        $conversionResult = DB::transaction(function () use ($ad, $user, $fromType, $toType, $uniqueAdTypeId) {
            $oldData = $ad->toArray();

            // Create destination sub-table record
            $this->createSubTableRecord($ad, $toType, $uniqueAdTypeId);

            // Clean up source sub-table record (keep data for audit trail)
            $this->removeSubTableRecord($ad, $fromType);

            // Update the ad type
            $ad->type = $toType;
            $ad->save();

            // Log the conversion
            $conversion = AdTypeConversion::create([
                'ad_id' => $ad->id,
                'user_id' => $user->id,
                'from_type' => $fromType,
                'to_type' => $toType,
                'unique_ad_type_id' => $uniqueAdTypeId,
            ]);

            $this->logAudit('converted', Ad::class, $ad->id, $oldData, [
                'type' => $toType,
                'conversion_id' => $conversion->id,
            ]);

            return $conversion;
        });

        $ad->refresh();

        return $this->success([
            'ad' => $ad->only(['id', 'type', 'title', 'slug']),
            'conversion' => $conversionResult->toArray(),
        ], "Ad converted from {$fromType} to {$toType} successfully");
    }

    /**
     * Get conversion history for an ad.
     *
     * GET /api/v1/ads/{ad}/conversions
     */
    public function history(Ad $ad): JsonResponse
    {
        $user = auth()->user();

        if ($ad->user_id !== $user->id && !$user->isAdmin()) {
            return $this->error(403, 'You do not own this ad');
        }

        $conversions = AdTypeConversion::where('ad_id', $ad->id)
            ->with('uniqueAdTypeDefinition')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($conversions, 'Ad conversion history retrieved');
    }

    // ========================================
    // PRIVATE METHODS
    // ========================================

    /**
     * Create the sub-table record for the destination type.
     */
    private function createSubTableRecord(Ad $ad, string $toType, ?int $uniqueAdTypeId = null): void
    {
        switch ($toType) {
            case PackageFeature::AD_TYPE_NORMAL:
                NormalAd::firstOrCreate(['ad_id' => $ad->id]);
                break;

            case PackageFeature::AD_TYPE_UNIQUE:
                $uniqueAd = UniqueAd::firstOrCreate(
                    ['ad_id' => $ad->id],
                    [
                        'unique_ad_type_id' => $uniqueAdTypeId,
                        'applies_caishha_feature' => false,
                    ]
                );
                // If record existed, update the type
                if ($uniqueAd->wasRecentlyCreated === false) {
                    $uniqueAd->update(['unique_ad_type_id' => $uniqueAdTypeId]);
                }
                // Apply type features
                if ($uniqueAdTypeId) {
                    $typeDef = UniqueAdTypeDefinition::find($uniqueAdTypeId);
                    if ($typeDef) {
                        $this->typeService->applyTypeFeatures($uniqueAd, $typeDef);
                    }
                }
                break;

            case PackageFeature::AD_TYPE_CAISHHA:
                // Create caishha record with defaults
                \App\Models\CaishhaAd::firstOrCreate(
                    ['ad_id' => $ad->id],
                    [
                        'offers_window_period' => 48,
                        'sellers_visibility_period' => 24,
                        'offers_count' => 0,
                    ]
                );
                break;
        }
    }

    /**
     * Remove the sub-table record for the source type (soft cleanup).
     */
    private function removeSubTableRecord(Ad $ad, string $fromType): void
    {
        switch ($fromType) {
            case PackageFeature::AD_TYPE_NORMAL:
                NormalAd::where('ad_id', $ad->id)->delete();
                break;

            case PackageFeature::AD_TYPE_UNIQUE:
                UniqueAd::where('ad_id', $ad->id)->delete();
                break;

            case PackageFeature::AD_TYPE_CAISHHA:
                \App\Models\CaishhaAd::where('ad_id', $ad->id)->delete();
                break;
        }
    }
}
