<?php

namespace Database\Factories;

use App\Models\Specification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Specification>
 */
class SpecificationFactory extends Factory
{
    protected $model = Specification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['text', 'number', 'select', 'boolean'];
        $type = fake()->randomElement($types);

        return [
            'name_en' => fake()->words(2, true),
            'name_ar' => fake()->optional()->words(2, true),
            'type' => $type,
            'values' => $type === 'select' ? ['Option 1', 'Option 2', 'Option 3'] : null,
            'image_id' => null,
        ];
    }

    /**
     * Indicate that the specification is of type select.
     */
    public function select(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'select',
            'values' => ['Automatic', 'Manual', 'Semi-Automatic'],
        ]);
    }

    /**
     * Indicate that the specification is of type number.
     */
    public function number(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'number',
            'values' => null,
        ]);
    }

    /**
     * Indicate that the specification is of type boolean.
     */
    public function boolean(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'boolean',
            'values' => null,
        ]);
    }
}
