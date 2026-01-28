<?php

namespace Database\Factories;

use App\Models\Ad;
use App\Models\Media;
use App\Models\UniqueAd;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UniqueAd>
 */
class UniqueAdFactory extends Factory
{
    protected $model = UniqueAd::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ad_id' => Ad::factory(),
            'banner_image_id' => null,
            'banner_color' => $this->faker->optional()->hexColor(),
            'is_auto_republished' => $this->faker->boolean(20),
            'is_verified_ad' => $this->faker->boolean(30),
            'is_featured' => false,
            'featured_at' => null,
        ];
    }

    /**
     * Indicate that the ad has a banner image.
     */
    public function withBannerImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'banner_image_id' => Media::factory(),
        ]);
    }

    /**
     * Indicate that the ad is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified_ad' => true,
        ]);
    }

    /**
     * Indicate that the ad is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'featured_at' => now(),
        ]);
    }

    /**
     * Indicate that the ad has auto-republishing enabled.
     */
    public function autoRepublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_auto_republished' => true,
        ]);
    }
}
