<?php

namespace Database\Factories;

use App\Models\Ad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NormalAd>
 */
class NormalAdFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ad_id' => Ad::factory()->normal(),
            'price_cash' => $this->faker->numberBetween(5000, 100000),
            'start_time' => now(),
            'update_time' => now(),
        ];
    }

    /**
     * Create a normal ad with a specific ad_id.
     */
    public function forAd(int $adId): static
    {
        return $this->state(fn (array $attributes) => [
            'ad_id' => $adId,
        ]);
    }
}