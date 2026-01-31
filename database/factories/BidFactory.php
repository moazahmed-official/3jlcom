<?php

namespace Database\Factories;

use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bid>
 */
class BidFactory extends Factory
{
    protected $model = Bid::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'auction_id' => Auction::factory(),
            'user_id' => User::factory(),
            'price' => $this->faker->numberBetween(1000, 100000),
        ];
    }

    /**
     * Set bid to a specific auction with proper pricing
     */
    public function forAuction(Auction $auction): static
    {
        return $this->state(function (array $attributes) use ($auction) {
            $minimumBid = $auction->getMinimumNextBid();
            $increment = $auction->minimum_bid_increment;
            
            return [
                'auction_id' => $auction->id,
                'price' => $minimumBid + ($increment * $this->faker->numberBetween(0, 5)),
            ];
        });
    }

    /**
     * Set bid at exactly the minimum required price
     */
    public function atMinimum(): static
    {
        return $this->afterMaking(function (Bid $bid) {
            if ($bid->auction) {
                $bid->price = $bid->auction->getMinimumNextBid();
            }
        });
    }

    /**
     * Set a high bid (significantly above minimum)
     */
    public function highBid(): static
    {
        return $this->afterMaking(function (Bid $bid) {
            if ($bid->auction) {
                $minimumBid = $bid->auction->getMinimumNextBid();
                $increment = $bid->auction->minimum_bid_increment;
                $bid->price = $minimumBid + ($increment * $this->faker->numberBetween(10, 50));
            }
        });
    }
}
