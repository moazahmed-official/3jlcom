<?php

namespace Database\Factories;

use App\Models\Ad;
use App\Models\CaishhaAd;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for CaishhaAd model
 */
class CaishhaAdFactory extends Factory
{
    protected $model = CaishhaAd::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'ad_id' => Ad::factory(),
            'offers_window_period' => \App\Models\CaishhaSetting::getDefaultDealerWindowSeconds(), // 36 hours default
            'offers_count' => 0,
            'sellers_visibility_period' => \App\Models\CaishhaSetting::getDefaultVisibilityPeriodSeconds(), // 36 hours default
        ];
    }

    /**
     * Indicate a short dealer window (1 hour).
     */
    public function shortDealerWindow(): static
    {
        return $this->state(fn (array $attributes) => [
            'offers_window_period' => 3600, // 1 hour
        ]);
    }

    /**
     * Indicate a long dealer window (7 days).
     */
    public function longDealerWindow(): static
    {
        return $this->state(fn (array $attributes) => [
            'offers_window_period' => 604800, // 7 days
        ]);
    }

    /**
     * Indicate a short visibility period (1 hour).
     */
    public function shortVisibilityPeriod(): static
    {
        return $this->state(fn (array $attributes) => [
            'sellers_visibility_period' => 3600, // 1 hour
        ]);
    }

    /**
     * Indicate no visibility delay (immediate).
     */
    public function immediateVisibility(): static
    {
        return $this->state(fn (array $attributes) => [
            'sellers_visibility_period' => 0,
        ]);
    }

    /**
     * Indicate a long visibility period (7 days).
     */
    public function longVisibilityPeriod(): static
    {
        return $this->state(fn (array $attributes) => [
            'sellers_visibility_period' => 604800, // 7 days
        ]);
    }

    /**
     * State with existing offers.
     */
    public function withOffers(int $count = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'offers_count' => $count,
        ]);
    }

    /**
     * State for a Caishha ad linked to a specific parent ad.
     */
    public function forAd(Ad $ad): static
    {
        return $this->state(fn (array $attributes) => [
            'ad_id' => $ad->id,
        ]);
    }
}
