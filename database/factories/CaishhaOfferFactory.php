<?php

namespace Database\Factories;

use App\Models\CaishhaOffer;
use App\Models\Ad;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for CaishhaOffer model
 */
class CaishhaOfferFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = CaishhaOffer::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'ad_id' => Ad::factory(),
            'user_id' => User::factory(),
            'price' => $this->faker->randomFloat(2, 1000, 100000),
            'comment' => $this->faker->optional(0.7)->sentence(),
            'status' => 'pending',
            'is_visible_to_seller' => false,
        ];
    }

    /**
     * Indicate that the offer is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the offer is accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'is_visible_to_seller' => true,
        ]);
    }

    /**
     * Indicate that the offer is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * Indicate that the offer is visible to seller.
     */
    public function visibleToSeller(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible_to_seller' => true,
        ]);
    }

    /**
     * Indicate that the offer has a high price.
     */
    public function highPrice(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $this->faker->randomFloat(2, 50000, 200000),
        ]);
    }

    /**
     * Indicate that the offer has a low price.
     */
    public function lowPrice(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $this->faker->randomFloat(2, 1000, 10000),
        ]);
    }

    /**
     * Create an offer for a specific ad.
     */
    public function forAd(Ad $ad): static
    {
        return $this->state(fn (array $attributes) => [
            'ad_id' => $ad->id,
        ]);
    }

    /**
     * Create an offer by a specific user.
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
