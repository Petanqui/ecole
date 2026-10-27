<?php

namespace Database\Factories\Attendance;

use App\Models\Attendance\AttendanceSession;
use App\Models\Grades\ClassSubject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceSession>
 */
class AttendanceSessionFactory extends Factory
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
            'held_on' => fake()->dateTimeBetween('-1 month'),
            'starts_at' => '08:00',
            'hours' => 1,
            'taken_by' => null,
        ];
    }
}
