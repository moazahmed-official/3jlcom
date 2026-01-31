<?php

namespace App\Console\Commands;

use App\Services\FindItMatchingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessFindItMatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'findit:process-matches 
                            {--request= : Process a specific request ID}
                            {--cleanup : Clean up old dismissed matches}
                            {--remove-invalid : Remove matches for inactive ads}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process FindIt requests and find matching ads';

    public function __construct(
        protected FindItMatchingService $matchingService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = microtime(true);
        
        $this->info('Starting FindIt matching process...');
        $this->newLine();

        // Handle cleanup option
        if ($this->option('cleanup')) {
            return $this->handleCleanup();
        }

        // Handle remove-invalid option
        if ($this->option('remove-invalid')) {
            return $this->handleRemoveInvalid();
        }

        // Handle specific request
        if ($requestId = $this->option('request')) {
            return $this->processSpecificRequest((int) $requestId);
        }

        // Process all active requests
        return $this->processAllRequests($startTime);
    }

    /**
     * Process a specific FindIt request.
     */
    protected function processSpecificRequest(int $requestId): int
    {
        $request = \App\Models\FinditRequest::find($requestId);

        if (!$request) {
            $this->error("FindIt request #{$requestId} not found.");
            return self::FAILURE;
        }

        if (!$request->isActive()) {
            $this->warn("FindIt request #{$requestId} is not active (status: {$request->status}).");
            
            if (!$this->confirm('Process anyway?')) {
                return self::SUCCESS;
            }
        }

        $this->info("Processing request #{$requestId}: {$request->title}");

        if ($this->option('dry-run')) {
            $this->warn('Dry run mode - no changes will be made.');
            $this->showRequestDetails($request);
            return self::SUCCESS;
        }

        try {
            $newMatches = $this->matchingService->processRequest($request);
            
            $this->info("Found {$newMatches} new matches.");
            $this->info("Total matches: " . $request->matches()->notDismissed()->count());
            
            Log::info("FindIt: Processed request #{$requestId}, found {$newMatches} new matches");
            
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error processing request: " . $e->getMessage());
            Log::error("FindIt: Error processing request #{$requestId}: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Process all active FindIt requests.
     */
    protected function processAllRequests(float $startTime): int
    {
        $activeCount = \App\Models\FinditRequest::active()
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->count();

        $this->info("Found {$activeCount} active FindIt requests.");

        if ($activeCount === 0) {
            $this->info('No active requests to process.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry run mode - no changes will be made.');
            $this->listActiveRequests();
            return self::SUCCESS;
        }

        $this->newLine();
        $progressBar = $this->output->createProgressBar($activeCount);
        $progressBar->start();

        $totalNewMatches = 0;
        $processedCount = 0;
        $errorCount = 0;

        \App\Models\FinditRequest::active()
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->chunk(20, function ($requests) use (&$totalNewMatches, &$processedCount, &$errorCount, $progressBar) {
                foreach ($requests as $request) {
                    try {
                        $newMatches = $this->matchingService->processRequest($request);
                        $totalNewMatches += $newMatches;
                        $processedCount++;
                    } catch (\Exception $e) {
                        $errorCount++;
                        Log::error("FindIt: Error processing request #{$request->id}: " . $e->getMessage());
                    }
                    
                    $progressBar->advance();
                }
            });

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $elapsed = round(microtime(true) - $startTime, 2);
        
        $this->info('Processing complete!');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Requests Processed', $processedCount],
                ['New Matches Found', $totalNewMatches],
                ['Errors', $errorCount],
                ['Time Elapsed', "{$elapsed}s"],
            ]
        );

        Log::info("FindIt: Batch processing complete. Processed: {$processedCount}, New matches: {$totalNewMatches}, Errors: {$errorCount}");

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Handle cleanup of old dismissed matches.
     */
    protected function handleCleanup(): int
    {
        $this->info('Cleaning up old dismissed matches...');

        if ($this->option('dry-run')) {
            $count = \App\Models\FinditMatch::where('dismissed', true)
                ->where('updated_at', '<', now()->subDays(30))
                ->count();
            
            $this->warn("Dry run: Would delete {$count} old dismissed matches.");
            return self::SUCCESS;
        }

        $deleted = $this->matchingService->cleanupOldMatches(30);
        
        $this->info("Deleted {$deleted} old dismissed matches.");
        Log::info("FindIt: Cleanup removed {$deleted} old dismissed matches");

        return self::SUCCESS;
    }

    /**
     * Handle removal of invalid matches (for inactive ads).
     */
    protected function handleRemoveInvalid(): int
    {
        $this->info('Removing matches for inactive ads...');

        if ($this->option('dry-run')) {
            $count = \App\Models\FinditMatch::whereHas('ad', function ($q) {
                $q->where('status', '!=', 'active');
            })->count();
            
            $this->warn("Dry run: Would delete {$count} invalid matches.");
            return self::SUCCESS;
        }

        $deleted = $this->matchingService->removeInvalidMatches();
        
        $this->info("Deleted {$deleted} invalid matches.");
        Log::info("FindIt: Removed {$deleted} matches for inactive ads");

        return self::SUCCESS;
    }

    /**
     * Show details about a specific request.
     */
    protected function showRequestDetails(\App\Models\FinditRequest $request): void
    {
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $request->id],
                ['Title', $request->title],
                ['Status', $request->status],
                ['User ID', $request->user_id],
                ['Brand ID', $request->brand_id ?? 'Any'],
                ['Model ID', $request->model_id ?? 'Any'],
                ['Price Range', ($request->min_price ?? '0') . ' - ' . ($request->max_price ?? 'Any')],
                ['Year Range', ($request->min_year ?? 'Any') . ' - ' . ($request->max_year ?? 'Any')],
                ['City ID', $request->city_id ?? 'Any'],
                ['Current Matches', $request->matches()->count()],
                ['Expires At', $request->expires_at?->toDateTimeString() ?? 'Never'],
                ['Last Matched', $request->last_matched_at?->toDateTimeString() ?? 'Never'],
            ]
        );
    }

    /**
     * List all active requests (for dry run).
     */
    protected function listActiveRequests(): void
    {
        $requests = \App\Models\FinditRequest::active()
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->withCount('matches')
            ->get(['id', 'title', 'user_id', 'last_matched_at']);

        $this->table(
            ['ID', 'Title', 'User', 'Current Matches', 'Last Matched'],
            $requests->map(function ($r) {
                return [
                    $r->id,
                    \Str::limit($r->title, 30),
                    $r->user_id,
                    $r->matches_count,
                    $r->last_matched_at?->diffForHumans() ?? 'Never',
                ];
            })->toArray()
        );
    }
}
