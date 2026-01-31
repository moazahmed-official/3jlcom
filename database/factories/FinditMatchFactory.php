<?php

namespace Database\Factories;

use App\Models\Ad;
use App\Models\FinditMatch;
use App\Models\FinditRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FinditMatch>
 */
class FinditMatchFactory extends Factory
{
    protected $model = FinditMatch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'findit_request_id' => FinditRequest::factory(),
            'ad_id' => Ad::factory(),
            'match_score' => $this->faker->numberBetween(30, 100),
            'notified_at' => null,
            'dismissed' => false,
        ];
    }

    /**
     * Set the match as new status.
     */
    public function statusNew(): static
    {
        return $this->state(fn (array $attributes) => [
            'dismissed' => false,
        ]);
    }

    /**
     * Set the match as viewed.
     */
    public function viewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'dismissed' => false,
        ]);
    }

    /**
     * Set the match as contacted.
     */
    public function contacted(): static
    {
        return $this->state(fn (array $attributes) => [
            'dismissed' => false,
        ]);
    }

    /**
     * Set the match as dismissed.
     */
    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'dismissed' => true,
        ]);
    }

    /**
     * Set specific request.
     */
    public function forRequest(FinditRequest $request): static
    {
        return $this->state(fn (array $attributes) => [
            'findit_request_id' => $request->id,
        ]);
    }

    /**
     * Set specific ad.
     */
    public function forAd(Ad $ad): static
    {
        return $this->state(fn (array $attributes) => [
            'ad_id' => $ad->id,
        ]);
    }

    /**
     * Set a high score match.
     */
    public function highScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'match_score' => $this->faker->numberBetween(80, 100),
        ]);
    }
}