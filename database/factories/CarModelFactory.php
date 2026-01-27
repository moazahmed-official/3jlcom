<?php

namespace Database\Factories;

use App\Models\CarModel;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CarModel>
 */
class CarModelFactory extends Factory
{
    protected $model = CarModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $models = [
            ['name_en' => 'Camry', 'name_ar' => 'كامري'],
            ['name_en' => 'Corolla', 'name_ar' => 'كورولا'],
            ['name_en' => 'Prius', 'name_ar' => 'بريوس'],
            ['name_en' => 'Altima', 'name_ar' => 'ألتيما'],
            ['name_en' => 'Sentra', 'name_ar' => 'سنترا'],
            ['name_en' => 'X5', 'name_ar' => 'إكس 5'],
            ['name_en' => '3 Series', 'name_ar' => 'الفئة الثالثة'],
            ['name_en' => 'C-Class', 'name_ar' => 'الفئة سي'],
            ['name_en' => 'E-Class', 'name_ar' => 'الفئة إي'],
        ];

        $model = $this->faker->randomElement($models);
        $currentYear = date('Y');
        $yearFrom = $this->faker->numberBetween(2000, $currentYear - 5);
        $yearTo = $this->faker->optional(0.7)->numberBetween($yearFrom, $currentYear + 1);

        return [
            'brand_id' => Brand::factory(),
            'name_en' => $model['name_en'],
            'name_ar' => $model['name_ar'],
            'year_from' => $yearFrom,
            'year_to' => $yearTo,
        ];
    }
}