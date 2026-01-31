<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Auction;

class UpdateAuctionTimes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auction:update-times';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update auction start times to make them active for testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $updated = Auction::whereHas('ad', function ($query) {
            $query->where('type', 'auction');
        })->update([
            'start_time' => now()->subHour(),
        ]);

        $this->info("Updated {$updated} auction start times to make them active.");

        return 0;
    }
}
