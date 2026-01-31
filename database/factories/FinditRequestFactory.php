<?php

namespace Database\Factories;

use App\Models\FinditRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FinditRequest>
 */
class FinditRequestFactory extends Factory
{
    protected $model = FinditRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $minPrice = $this->faker->randomElement([10000, 15000, 20000, 25000, 30000]);
        $maxPrice = $minPrice + $this->faker->numberBetween(5000, 20000);
        
        $minYear = $this->faker->numberBetween(2018, 2022);
        $maxYear = $minYear + $this->faker->numberBetween(1, 3);

        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'brand_id' => null,
            'model_id' => null,
            'category_id' => null,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'min_year' => $minYear,
            'max_year' => $maxYear,
            'min_mileage' => null,
            'max_mileage' => $this->faker->randomElement([50000, 80000, 100000, null]),
            'city_id' => null,
            'country_id' => null,
            'transmission' => $this->faker->randomElement(['automatic', 'manual', null]),
            'fuel_type' => $this->faker->randomElement(['petrol', 'diesel', 'hybrid', 'electric', null]),
            'body_type' => $this->faker->randomElement(['sedan', 'suv', 'hatchback', 'coupe', null]),
            'color' => $this->faker->randomElement(['white', 'black', 'silver', 'blue', null]),
            'condition' => $this->faker->randomElement(['new', 'used', 'certified', null]),
            'status' => 'draft',
            'media_count' => 0,
            'offers_count' => 0,
            'matches_count' => 0,
            'expires_at' => now()->addDays(30),
            'last_matched_at' => null,
        ];
    }

    /**
     * Set the request as active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Set the request as draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Set the request as closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
        ]);
    }

    /**
     * Set the request as expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => now()->subDays(1),
        ]);
    }

    /**
     * Set specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
