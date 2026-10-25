<?php

namespace Database\Factories\Grades;

use App\Models\Grades\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'name' => ucfirst(fake()->unique()->word()),
            'sort_order' => 0,
        ];
    }
}
