<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\FinditMatch;
use App\Models\FinditRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FindItMatchingService
{
    /**
     * Weight configuration for match scoring.
     * Total should add up to 100 for percentage-based scoring.
     */
    protected array $weights = [
        'brand' => 25,
        'model' => 20,
        'price' => 20,
        'year' => 15,
        'city' => 10,
        'condition' => 10,
    ];

    /**
     * Minimum score threshold for creating a match.
     */
    protected int $minScoreThreshold = 20;

    /**
     * Maximum matches to store per request.
     */
    protected int $maxMatchesPerRequest = 100;

    /**
     * Process all active FindIt requests.
     * 
     * @return int Total number of new matches found
     */
    public function processAllActiveRequests(): int
    {
        $totalNewMatches = 0;

        FinditRequest::active()
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->chunk(50, function ($requests) use (&$totalNewMatches) {
                foreach ($requests as $request) {
                    try {
                        $newMatches = $this->processRequest($request);
                        $totalNewMatches += $newMatches;
                    } catch (\Exception $e) {
                        Log::error("Error processing FindIt request {$request->id}: " . $e->getMessage());
                    }
                }
            });

        return $totalNewMatches;
    }

    /**
     * Process a single FindIt request and find matching ads.
     * 
     * @param FinditRequest $request
     * @return int Number of new matches found
     */
    public function processRequest(FinditRequest $request): int
    {
        if (!$request->isActive()) {
            Log::info("FindIt request {$request->id} is not active, skipping");
            return 0;
        }

        // Get existing match ad IDs to avoid duplicates
        $existingAdIds = $request->matches()->pluck('ad_id')->toArray();

        Log::info("Processing FindIt request {$request->id}, excluding " . count($existingAdIds) . " existing matches");

        // Build query for potential matches across ALL ad types
        $query = $this->buildMatchingQuery($request, $existingAdIds);

        // Fetch candidates with all related ad type data
        $candidates = $query->with(['normalAd', 'uniqueAd', 'caishhaAd', 'auction', 'brand', 'model', 'city'])->get();

        Log::info("Found {$candidates->count()} candidate ads for FindIt request {$request->id}");

        if ($candidates->isEmpty()) {
            $this->updateRequestMatchedAt($request);
            return 0;
        }

        // Calculate scores and filter
        $scoredMatches = $this->scoreMatches($request, $candidates);

        Log::info("Scored {$scoredMatches->count()} matches above threshold for FindIt request {$request->id}");

        // Store new matches
        $newMatchesCount = $this->storeMatches($request, $scoredMatches);

        // Update request timestamp
        $this->updateRequestMatchedAt($request);

        return $newMatchesCount;
    }

    /**
     * Build the base query for finding potential matching ads across ALL ad types.
     */
    protected function buildMatchingQuery(FinditRequest $request, array $excludeAdIds = []): Builder
    {
        // Start with base query on ads table - include ALL types
        $query = Ad::query()
            ->where('ads.status', 'published')
            ->where('ads.user_id', '!=', $request->user_id); // Don't match user's own ads

        // Exclude already matched ads
        if (!empty($excludeAdIds)) {
            $query->whereNotIn('ads.id', $excludeAdIds);
        }

        // IMPORTANT: We don't strictly filter here - we want to find as many candidates as possible
        // and let the scoring algorithm determine relevance
        
        // Brand filter - soft filter (include if specified, but don't exclude others completely)
        if ($request->brand_id) {
            // Prioritize matching brand but don't exclude others
            $query->orderByRaw('CASE WHEN ads.brand_id = ? THEN 0 ELSE 1 END', [$request->brand_id]);
        }

        // Year range filter - broad tolerance
        if ($request->min_year || $request->max_year) {
            $query->where(function ($q) use ($request) {
                // Include ads within extended range or with no year specified
                $minYear = $request->min_year ? $request->min_year - 3 : 1900;
                $maxYear = $request->max_year ? $request->max_year + 3 : 2099;
                
                $q->whereBetween('ads.year', [$minYear, $maxYear])
                  ->orWhereNull('ads.year');
            });
        }

        // Location preference - don't exclude, just for ordering
        if ($request->city_id) {
            $query->orderByRaw('CASE WHEN ads.city_id = ? THEN 0 ELSE 1 END', [$request->city_id]);
        }

        // Order by newest first
        $query->orderBy('ads.created_at', 'desc');

        // Limit candidates for performance
        $query->limit(500);

        return $query;
    }

    /**
     * Calculate match scores for candidate ads.
     * 
     * @return Collection Collection of [ad_id => score] sorted by score desc
     */
    protected function scoreMatches(FinditRequest $request, Collection $candidates): Collection
    {
        $scored = collect();

        foreach ($candidates as $ad) {
            $score = $this->calculateScore($request, $ad);
            
            Log::debug("Ad {$ad->id} ({$ad->type}) scored {$score} for FindIt request {$request->id}");
            
            if ($score >= $this->minScoreThreshold) {
                $scored->push([
                    'ad_id' => $ad->id,
                    'score' => $score,
                ]);
            }
        }

        // Sort by score descending and limit
        return $scored->sortByDesc('score')
            ->take($this->maxMatchesPerRequest)
            ->values();
    }

    /**
     * Calculate match score for a single ad against the request criteria.
     */
    protected function calculateScore(FinditRequest $request, Ad $ad): int
    {
        $score = 0;
        $maxPossibleScore = 0;

        // Brand score
        if ($request->brand_id) {
            $maxPossibleScore += $this->weights['brand'];
            if ($ad->brand_id === $request->brand_id) {
                $score += $this->weights['brand'];
            }
        }

        // Model score
        if ($request->model_id) {
            $maxPossibleScore += $this->weights['model'];
            if ($ad->model_id === $request->model_id) {
                $score += $this->weights['model'];
            }
        }

        // Price score - check across all ad types
        if ($request->min_price || $request->max_price) {
            $maxPossibleScore += $this->weights['price'];
            $priceScore = $this->calculatePriceScore($request, $ad);
            $score += $priceScore;
        }

        // Year score
        if ($request->min_year || $request->max_year) {
            $maxPossibleScore += $this->weights['year'];
            $yearScore = $this->calculateYearScore($request, $ad);
            $score += $yearScore;
        }

        // City score
        if ($request->city_id) {
            $maxPossibleScore += $this->weights['city'];
            if ($ad->city_id === $request->city_id) {
                $score += $this->weights['city'];
            }
        }

        // Condition score (if both have condition info)
        if ($request->condition || $request->condition_rating) {
            $maxPossibleScore += $this->weights['condition'];
            // For now, give partial score if ad exists (we can enhance this later)
            $score += $this->weights['condition'] * 0.5;
        }

        // If no criteria specified, give base score
        if ($maxPossibleScore === 0) {
            return 50; // Default 50% match for requests with no criteria
        }

        // Normalize to percentage
        return (int) round(($score / $maxPossibleScore) * 100);
    }

    /**
     * Get the price for an ad, checking all ad types.
     */
    protected function getAdPrice(Ad $ad): ?float
    {
        // Check based on ad type
        switch ($ad->type) {
            case 'normal':
                return $ad->normalAd?->price_cash ? (float) $ad->normalAd->price_cash : null;
            
            case 'unique':
                // Unique ads might store price in normalAd relation or not have a fixed price
                return $ad->normalAd?->price_cash ? (float) $ad->normalAd->price_cash : null;
            
            case 'auction':
                // For auctions, use start_price or current highest bid
                if ($ad->auction) {
                    return (float) ($ad->auction->last_price ?? $ad->auction->start_price);
                }
                return null;
            
            case 'caishha':
                // Caishha ads might not have a fixed price (offers-based)
                return null;
            
            default:
                return null;
        }
    }

    /**
     * Calculate price-based score component.
     */
    protected function calculatePriceScore(FinditRequest $request, Ad $ad): int
    {
        $price = $this->getAdPrice($ad);
        
        // If no price available, give partial score
        if ($price === null) {
            return (int) ($this->weights['price'] * 0.3); // 30% partial score
        }
        
        $minPrice = $request->min_price ? (float) $request->min_price : 0;
        $maxPrice = $request->max_price ? (float) $request->max_price : PHP_INT_MAX;

        // Perfect match within range
        if ($price >= $minPrice && $price <= $maxPrice) {
            return $this->weights['price'];
        }

        // Calculate how far outside the range
        if ($price < $minPrice && $minPrice > 0) {
            $diff = ($minPrice - $price) / $minPrice;
            // Reduce score based on how far below (up to 50% tolerance)
            if ($diff <= 0.5) {
                return (int) ($this->weights['price'] * (1 - $diff));
            }
            return 0;
        }

        if ($price > $maxPrice && $maxPrice > 0 && $maxPrice < PHP_INT_MAX) {
            $diff = ($price - $maxPrice) / $maxPrice;
            // Reduce score based on how far above (up to 50% tolerance)
            if ($diff <= 0.5) {
                return (int) ($this->weights['price'] * (1 - $diff));
            }
            return 0;
        }

        return 0;
    }

    /**
     * Calculate year-based score component.
     */
    protected function calculateYearScore(FinditRequest $request, Ad $ad): int
    {
        $year = $ad->year;
        
        if (!$year) {
            return (int) ($this->weights['year'] * 0.3); // 30% partial score if no year
        }

        $minYear = $request->min_year ?? 1900;
        $maxYear = $request->max_year ?? 2099;

        // Perfect match within range
        if ($year >= $minYear && $year <= $maxYear) {
            return $this->weights['year'];
        }

        // Calculate score reduction for years outside range
        if ($year < $minYear) {
            $diff = $minYear - $year;
            // Lose 20% per year outside range, up to 5 years
            if ($diff <= 5) {
                return (int) ($this->weights['year'] * (1 - ($diff * 0.2)));
            }
            return 0;
        }

        if ($year > $maxYear) {
            $diff = $year - $maxYear;
            if ($diff <= 5) {
                return (int) ($this->weights['year'] * (1 - ($diff * 0.2)));
            }
            return 0;
        }

        return 0;
    }

    /**
     * Store scored matches in the database.
     */
    protected function storeMatches(FinditRequest $request, Collection $scoredMatches): int
    {
        if ($scoredMatches->isEmpty()) {
            return 0;
        }

        $insertData = $scoredMatches->map(function ($match) use ($request) {
            return [
                'findit_request_id' => $request->id,
                'ad_id' => $match['ad_id'],
                'match_score' => $match['score'],
                'dismissed' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        // Batch insert
        DB::table('findit_matches')->insert($insertData);

        // Update counter on request
        $request->updateCounters();

        Log::info("Stored {$request->matches_count} matches for FindIt request {$request->id}");

        return count($insertData);
    }

    /**
     * Update the last_matched_at timestamp on the request.
     */
    protected function updateRequestMatchedAt(FinditRequest $request): void
    {
        $request->update(['last_matched_at' => now()]);
    }

    /**
     * Find new matches since last processing.
     * Only looks at ads created after the last matching run.
     */
    public function findNewMatchesSinceLastRun(FinditRequest $request): int
    {
        if (!$request->isActive()) {
            return 0;
        }

        $lastMatched = $request->last_matched_at ?? $request->created_at;
        $existingAdIds = $request->matches()->pluck('ad_id')->toArray();

        $query = $this->buildMatchingQuery($request, $existingAdIds)
            ->where('ads.created_at', '>', $lastMatched);

        $candidates = $query->with(['normalAd', 'uniqueAd', 'caishhaAd', 'auction'])->get();

        if ($candidates->isEmpty()) {
            return 0;
        }

        $scoredMatches = $this->scoreMatches($request, $candidates);
        
        return $this->storeMatches($request, $scoredMatches);
    }

    /**
     * Refresh matches for a request - clear and re-find all matches.
     */
    public function refreshMatches(FinditRequest $request): int
    {
        // Delete all existing matches for this request
        $request->matches()->delete();
        
        // Reset the counter
        $request->update(['matches_count' => 0]);
        
        // Re-process to find new matches
        return $this->processRequest($request);
    }

    /**
     * Clean up old/dismissed matches.
     */
    public function cleanupOldMatches(int $daysOld = 30): int
    {
        return FinditMatch::where('dismissed', true)
            ->where('updated_at', '<', now()->subDays($daysOld))
            ->delete();
    }

    /**
     * Remove matches for ads that are no longer published.
     */
    public function removeInvalidMatches(): int
    {
        return FinditMatch::whereHas('ad', function ($q) {
            $q->where('status', '!=', 'published');
        })->delete();
    }
}
