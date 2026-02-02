<?php

namespace Database\Factories;

use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavedSearchFactory extends Factory
{
    protected $model = SavedSearch::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'query_params' => [
                'brand_id' => $this->faker->numberBetween(1, 50),
                'city_id' => $this->faker->numberBetween(1, 100),
                'min_price' => $this->faker->numberBetween(1000, 5000),
                'max_price' => $this->faker->numberBetween(10000, 50000),
                'year_min' => $this->faker->numberBetween(2010, 2020),
            ],
        ];
    }
}
