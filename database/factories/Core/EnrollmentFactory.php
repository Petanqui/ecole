<?php

namespace Database\Factories\Core;

use App\Models\Core\Enrollment;
use App\Models\Core\SchoolClass;
use App\Models\Core\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'school_class_id' => SchoolClass::factory(),
            'enrolled_on' => fake()->dateTimeBetween('-1 month'),
        ];
    }
}
