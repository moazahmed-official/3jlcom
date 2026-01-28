<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Category;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ad>
 */
class AdFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'normal',
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'model_id' => CarModel::factory(),
            'city_id' => City::factory(),
            'country_id' => Country::factory(),
            'year' => $this->faker->numberBetween(2000, 2024),
            'price_cash' => $this->faker->numberBetween(5000, 100000),
            'banner_color' => $this->faker->hexColor(),
            'is_verified_ad' => false,
            'views_count' => $this->faker->numberBetween(0, 1000),
            'status' => 'published',
        ];
    }

    /**
     * Indicate that the ad is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Indicate that the ad is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the ad is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
        ]);
    }

    /**
     * Indicate that the ad is a normal ad.
     */
    public function normal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'normal',
        ]);
    }

    /**
     * Indicate that the ad is a unique ad.
     */
    public function unique(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'unique',
        ]);
    }

    /**
     * Indicate that the ad is a caishha ad.
     */
    public function caishha(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'caishha',
        ]);
    }
}