<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $brands = [
            ['name_en' => 'Toyota', 'name_ar' => 'تويوتا'],
            ['name_en' => 'Nissan', 'name_ar' => 'نيسان'],
            ['name_en' => 'BMW', 'name_ar' => 'بي إم دبليو'],
            ['name_en' => 'Mercedes', 'name_ar' => 'مرسيدس'],
            ['name_en' => 'Audi', 'name_ar' => 'أودي'],
            ['name_en' => 'Honda', 'name_ar' => 'هوندا'],
            ['name_en' => 'Hyundai', 'name_ar' => 'هيونداي'],
            ['name_en' => 'Kia', 'name_ar' => 'كيا'],
            ['name_en' => 'Mazda', 'name_ar' => 'مازدا'],
            ['name_en' => 'Ford', 'name_ar' => 'فورد'],
        ];

        $brand = $this->faker->randomElement($brands);

        return [
            'name_en' => $brand['name_en'],
            'name_ar' => $brand['name_ar'],
        ];
    }
}