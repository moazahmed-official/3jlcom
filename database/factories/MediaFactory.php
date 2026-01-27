<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        $purposes = ['ad', 'profile', 'general', 'brand', 'model'];
        $types = ['image', 'video'];
        $type = $this->faker->randomElement($types);
        $extension = $type === 'image' ? 'jpg' : 'mp4';

        return [
            'user_id' => User::factory(),
            'file_name' => $this->faker->uuid() . '.' . $extension,
            'path' => 'media/' . $this->faker->uuid() . '.' . $extension,
            'type' => $type,
            'status' => 'ready',
            'thumbnail_url' => $type === 'image' ? null : 'thumbnails/' . $this->faker->uuid() . '_thumb.jpg',
            'related_resource' => $this->faker->randomElement(['ads', 'users', 'brands', null]),
            'related_id' => $this->faker->optional()->randomNumber(3),
        ];
    }

    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'image',
            'file_name' => str_replace('.mp4', '.jpg', $attributes['file_name']),
            'path' => str_replace('.mp4', '.jpg', $attributes['path']),
            'thumbnail_url' => null,
        ]);
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'video',
            'thumbnail_url' => 'thumbnails/' . $this->faker->uuid() . '_thumb.jpg',
        ]);
    }

    public function forPurpose(string $purpose): static
    {
        return $this->state(fn (array $attributes) => [
            'related_resource' => $purpose === 'general' ? null : $purpose . 's',
        ]);
    }
}