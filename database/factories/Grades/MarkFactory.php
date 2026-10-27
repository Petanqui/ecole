<?php

namespace Database\Factories\Grades;

use App\Models\Core\Student;
use App\Models\Grades\Assessment;
use App\Models\Grades\Mark;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mark>
 */
class MarkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'student_id' => Student::factory(),
            'score' => fake()->randomFloat(2, 0, 20),
            'is_absent' => false,
        ];
    }
}
