<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Traits\LogsAudit;
use App\Models\Ad;
use App\Models\FeatureUsageLog;
use App\Services\FeatureUsageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeatureActionController extends BaseApiController
{
    use LogsAudit;

    protected FeatureUsageService $featureService;

    public function __construct(FeatureUsageService $featureService)
    {
        $this->featureService = $featureService;
    }

    /**
     * Get available feature credits for the authenticated user.
     *
     * GET /api/v1/feature-credits
     */
    public function credits(Request $request): JsonResponse
    {
        $user = auth()->user();
        $allCredits = $this->featureService->getAllCredits($user);

        return $this->success($allCredits, 'Feature credits retrieved');
    }

    /**
     * Get feature usage history for the authenticated user.
     *
     * GET /api/v1/feature-usage
     */
    public function usageHistory(Request $request): JsonResponse
    {
        $user = auth()->user();

        $history = $this->featureService->getUsageHistory(
            $user,
            $request->query('feature'),
            $request->query('ad_id') ? (int) $request->query('ad_id') : null,
            $request->query('limit', 50)
        );

        return $this->success($history, 'Feature usage history retrieved');
    }

    /**
     * Push an ad to Facebook.
     *
     * POST /api/v1/ads/{ad}/push-facebook
     */
    public function pushToFacebook(Request $request, Ad $ad): JsonResponse
    {
        return $this->executeFeatureAction(
            $ad,
            FeatureUsageLog::FEATURE_FACEBOOK_PUSH,
            'Facebook push',
            $request->all()
        );
    }

    /**
     * Generate an AI video for an ad.
     *
     * POST /api/v1/ads/{ad}/ai-video
     */
    public function generateAiVideo(Request $request, Ad $ad): JsonResponse
    {
        $request->validate([
            'style' => 'nullable|string|max:50',
            'duration' => 'nullable|integer|min:5|max:60',
        ]);

        return $this->executeFeatureAction(
            $ad,
            FeatureUsageLog::FEATURE_AI_VIDEO,
            'AI video generation',
            $request->only(['style', 'duration'])
        );
    }

    /**
     * Apply auto-background removal/editing on an ad image.
     *
     * POST /api/v1/ads/{ad}/auto-bg
     */
    public function autoBg(Request $request, Ad $ad): JsonResponse
    {
        $request->validate([
            'media_id' => 'required|integer|exists:media,id',
            'background_type' => 'nullable|string|in:transparent,blur,color,scene',
            'background_value' => 'nullable|string',
        ]);

        return $this->executeFeatureAction(
            $ad,
            FeatureUsageLog::FEATURE_AUTO_BG,
            'auto-background',
            $request->only(['media_id', 'background_type', 'background_value'])
        );
    }

    /**
     * Apply Pixblin image editing on an ad image.
     *
     * POST /api/v1/ads/{ad}/pixblin
     */
    public function pixblin(Request $request, Ad $ad): JsonResponse
    {
        $request->validate([
            'media_id' => 'required|integer|exists:media,id',
            'edits' => 'nullable|array',
        ]);

        return $this->executeFeatureAction(
            $ad,
            FeatureUsageLog::FEATURE_PIXBLIN,
            'Pixblin image editing',
            $request->only(['media_id', 'edits'])
        );
    }

    /**
     * Use Carseer API for an ad (vehicle inspection/data).
     *
     * POST /api/v1/ads/{ad}/carseer
     */
    public function carseer(Request $request, Ad $ad): JsonResponse
    {
        $request->validate([
            'vin' => 'nullable|string|max:17',
            'inspection_type' => 'nullable|string|in:basic,full',
        ]);

        return $this->executeFeatureAction(
            $ad,
            FeatureUsageLog::FEATURE_CARSEER,
            'Carseer API',
            $request->only(['vin', 'inspection_type'])
        );
    }

    // ========================================
    // PRIVATE METHODS
    // ========================================

    /**
     * Execute a feature action: verify ownership, check credits, consume, and return result.
     */
    private function executeFeatureAction(Ad $ad, string $feature, string $featureLabel, array $metadata = []): JsonResponse
    {
        $user = auth()->user();

        // Verify ownership
        if ($ad->user_id !== $user->id && !$user->isAdmin()) {
            return $this->error(403, 'You do not own this ad');
        }

        // Check and consume credits
        $result = $this->featureService->consumeCredits($user, $feature, $ad->id, 1, $metadata);

        if (!$result['success']) {
            return $this->error(403, $result['reason'], [
                'feature' => [$result['reason']],
                'remaining' => $result['remaining'],
            ]);
        }

        $this->logAudit('feature_used', Ad::class, $ad->id, null, [
            'feature' => $feature,
            'credits_used' => 1,
            'remaining' => $result['remaining'],
            'metadata' => $metadata,
        ]);

        return $this->success([
            'feature' => $feature,
            'ad_id' => $ad->id,
            'credits_remaining' => $result['remaining'],
            'usage_log_id' => $result['log_id'],
            'message' => "{$featureLabel} action initiated successfully.",
        ], "{$featureLabel} credits consumed. Processing will begin shortly.");
    }
}
