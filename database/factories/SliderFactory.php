<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\Slider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Slider>
 */
class SliderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Slider::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'image_id' => null,
            'category_id' => null,
            'value' => fake()->optional(0.7)->url(),
            'status' => fake()->randomElement(['active', 'inactive']),
        ];
    }

    /**
     * Indicate that the slider is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the slider is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Set the slider category.
     */
    public function category(int $categoryId): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $categoryId,
        ]);
    }

    /**
     * Associate with a media record.
     */
    public function withMedia(?Media $media = null): static
    {
        return $this->state(function (array $attributes) use ($media) {
            if ($media) {
                return ['image_id' => $media->id];
            }
            
            // Create a new media if none provided
            $newMedia = Media::factory()->create();
            return ['image_id' => $newMedia->id];
        });
    }
}
