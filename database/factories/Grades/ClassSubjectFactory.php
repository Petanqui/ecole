<?php

namespace Database\Factories\Grades;

use App\Models\Core\SchoolClass;
use App\Models\Grades\ClassSubject;
use App\Models\Grades\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSubject>
 */
class ClassSubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_class_id' => SchoolClass::factory(),
            'subject_id' => Subject::factory(),
            'coefficient' => fake()->numberBetween(1, 5),
            'teacher_id' => null,
        ];
    }
}
