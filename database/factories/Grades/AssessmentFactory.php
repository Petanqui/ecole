<?php

namespace Database\Factories\Grades;

use App\Enums\Grades\AssessmentType;
use App\Models\Grades\Assessment;
use App\Models\Grades\ClassSubject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Give the assessment a term of the class's school year, e.g.
 * `->for($classSubject)->for($classSubject->schoolClass->schoolYear->terms->first())`.
 *
 * @extends Factory<Assessment>
 */
class AssessmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_subject_id' => ClassSubject::factory(),
            'term_id' => fn (array $attributes) => ClassSubject::find($attributes['class_subject_id'])->schoolClass->schoolYear->terms()->first()->id,
            'type' => AssessmentType::Homework,
            'title' => null,
            'held_on' => fake()->dateTimeBetween('-1 month'),
            'max_score' => 20,
        ];
    }
}
