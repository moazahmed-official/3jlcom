<?php

namespace App\Console\Commands;

use App\Models\Auction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CloseExpiredAuctions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auctions:close-expired 
                            {--dry-run : Preview which auctions would be closed without actually closing them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close all auctions that have ended and have auto_close enabled';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info($isDryRun 
            ? 'Running in dry-run mode - no auctions will be closed'
            : 'Checking for expired auctions to close...'
        );

        // Get all auctions ready for auto-close
        $auctions = Auction::readyForAutoClose()->with(['ad', 'bids'])->get();

        if ($auctions->isEmpty()) {
            $this->info('No expired auctions found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$auctions->count()} auction(s) to close.");

        $successCount = 0;
        $failureCount = 0;

        foreach ($auctions as $auction) {
            $adTitle = $auction->ad?->title ?? 'Unknown';
            
            if ($isDryRun) {
                $this->line("  [DRY RUN] Would close auction #{$auction->id} - {$adTitle}");
                $this->line("            End time: {$auction->end_time}");
                $this->line("            Bid count: {$auction->bid_count}");
                $this->line("            Last price: " . ($auction->last_price ?? 'No bids'));
                continue;
            }

            try {
                DB::beginTransaction();

                $result = $auction->closeAuction();

                DB::commit();

                $successCount++;

                $message = $result['reserve_met'] 
                    ? "Closed with winner (User #{$result['winner_id']}) at {$result['winning_bid']}"
                    : ($result['winner_id'] ? "Closed but reserve not met" : "Closed with no bids");

                $this->info("  ✓ Closed auction #{$auction->id} - {$adTitle}: {$message}");

                Log::info('Auction auto-closed', [
                    'auction_id' => $auction->id,
                    'ad_id' => $auction->ad_id,
                    'winner_id' => $result['winner_id'],
                    'winning_bid' => $result['winning_bid'],
                    'reserve_met' => $result['reserve_met'],
                ]);

            } catch (\Exception $e) {
                DB::rollback();
                $failureCount++;

                $this->error("  ✗ Failed to close auction #{$auction->id} - {$adTitle}: {$e->getMessage()}");

                Log::error('Auction auto-close failed', [
                    'auction_id' => $auction->id,
                    'ad_id' => $auction->ad_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        if (!$isDryRun) {
            $this->newLine();
            $this->info("Summary: {$successCount} closed successfully, {$failureCount} failed.");
        }

        return $failureCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
