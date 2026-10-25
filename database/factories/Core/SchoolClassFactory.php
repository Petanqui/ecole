<?php

namespace Database\Factories\Core;

use App\Enums\Core\GradeLevel;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolClass>
 */
class SchoolClassFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $level = fake()->randomElement(GradeLevel::cases());

        return [
            'school_year_id' => SchoolYear::factory(),
            'level' => $level,
            'name' => $level->getLabel().' '.fake()->unique()->bothify('?#'),
            'capacity' => fake()->numberBetween(25, 60),
        ];
    }
}
