<?php

namespace Database\Factories\Grades;

use App\Enums\Grades\AdmissionExam;
use App\Models\Core\Enrollment;
use App\Models\Grades\AdmissionExamScore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdmissionExamScore>
 */
class AdmissionExamScoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'exam' => fake()->randomElement(AdmissionExam::cases()),
            'score' => fake()->randomFloat(2, 0, 20),
        ];
    }
}
