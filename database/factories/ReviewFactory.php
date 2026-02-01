<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\User;
use App\Models\Ad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'seller_id' => User::factory(),
            'ad_id' => null,
            'title' => fake()->sentence(rand(3, 6)),
            'body' => fake()->paragraph(rand(3, 8)),
            'stars' => fake()->numberBetween(1, 5),
        ];
    }

    /**
     * Indicate the review is for an ad
     */
    public function forAd(): static
    {
        return $this->state(fn (array $attributes) => [
            'ad_id' => Ad::factory(),
        ]);
    }

    /**
     * Indicate the review is for a seller only (no specific ad)
     */
    public function forSeller(): static
    {
        return $this->state(fn (array $attributes) => [
            'ad_id' => null,
        ]);
    }

    /**
     * Indicate the review has high rating (4-5 stars)
     */
    public function highRating(): static
    {
        return $this->state(fn (array $attributes) => [
            'stars' => fake()->numberBetween(4, 5),
            'title' => 'Great ' . fake()->word() . '!',
            'body' => 'I am very satisfied with ' . fake()->sentence(),
        ]);
    }

    /**
     * Indicate the review has low rating (1-2 stars)
     */
    public function lowRating(): static
    {
        return $this->state(fn (array $attributes) => [
            'stars' => fake()->numberBetween(1, 2),
            'title' => 'Poor ' . fake()->word(),
            'body' => 'I am disappointed because ' . fake()->sentence(),
        ]);
    }

    /**
     * Indicate the review has medium rating (3 stars)
     */
    public function mediumRating(): static
    {
        return $this->state(fn (array $attributes) => [
            'stars' => 3,
            'title' => 'Average ' . fake()->word(),
            'body' => 'It was okay, ' . fake()->sentence(),
        ]);
    }

    /**
     * Create a review with specific user and seller
     */
    public function between(User $reviewer, User $seller): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $reviewer->id,
            'seller_id' => $seller->id,
        ]);
    }

    /**
     * Create a review for a specific ad
     */
    public function onAd(Ad $ad): static
    {
        return $this->state(fn (array $attributes) => [
            'ad_id' => $ad->id,
            'seller_id' => $ad->user_id,
        ]);
    }
}
