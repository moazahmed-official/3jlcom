<?php

namespace Database\Factories;

use App\Models\Ad;
use App\Models\Auction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Auction>
 */
class AuctionFactory extends Factory
{
    protected $model = Auction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = $this->faker->dateTimeBetween('now', '+1 day');
        $endTime = $this->faker->dateTimeBetween($startTime, '+7 days');
        $startPrice = $this->faker->numberBetween(1000, 50000);
        
        return [
            'ad_id' => Ad::factory(),
            'start_price' => $startPrice,
            'reserve_price' => $this->faker->optional(0.5)->numberBetween($startPrice, $startPrice * 2),
            'last_price' => null,
            'minimum_bid_increment' => $this->faker->randomElement([50, 100, 200, 500]),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'auto_close' => true,
            'is_last_price_visible' => $this->faker->boolean(80),
            'anti_snip_window_seconds' => 300,
            'anti_snip_extension_seconds' => 300,
            'status' => 'active',
            'bid_count' => 0,
            'winner_id' => null,
        ];
    }

    /**
     * Indicate that the auction is active and accepting bids
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => now()->subMinutes(30),
            'end_time' => now()->addDays(3),
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the auction is upcoming (hasn't started yet)
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => now()->addDay(),
            'end_time' => now()->addDays(4),
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the auction has ended (past end_time but not closed)
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => now()->subDays(7),
            'end_time' => now()->subHour(),
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the auction is closed
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => now()->subDays(7),
            'end_time' => now()->subDay(),
            'status' => 'closed',
        ]);
    }

    /**
     * Indicate that the auction is cancelled
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Indicate that the auction is ending soon (within 1 hour)
     */
    public function endingSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => now()->subDays(3),
            'end_time' => now()->addMinutes(30),
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the auction has bids
     */
    public function withBids(int $count = 3): static
    {
        $startPrice = $this->faker->numberBetween(1000, 50000);
        $increment = $this->faker->randomElement([50, 100, 200, 500]);
        $lastPrice = $startPrice + ($increment * $count);
        
        return $this->state(fn (array $attributes) => [
            'start_price' => $startPrice,
            'minimum_bid_increment' => $increment,
            'last_price' => $lastPrice,
            'bid_count' => $count,
        ]);
    }

    /**
     * Indicate that the auction has a reserve price
     */
    public function withReserve(int $reservePrice = null): static
    {
        return $this->state(function (array $attributes) use ($reservePrice) {
            $startPrice = $attributes['start_price'] ?? 1000;
            return [
                'reserve_price' => $reservePrice ?? ($startPrice * 1.5),
            ];
        });
    }

    /**
     * Indicate that the auction has no reserve price
     */
    public function noReserve(): static
    {
        return $this->state(fn (array $attributes) => [
            'reserve_price' => null,
        ]);
    }

    /**
     * Indicate that the last price is hidden
     */
    public function hiddenPrice(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_last_price_visible' => false,
        ]);
    }

    /**
     * Indicate custom anti-snipe settings
     */
    public function antiSnipe(int $windowSeconds = 300, int $extensionSeconds = 300): static
    {
        return $this->state(fn (array $attributes) => [
            'anti_snip_window_seconds' => $windowSeconds,
            'anti_snip_extension_seconds' => $extensionSeconds,
        ]);
    }
}
