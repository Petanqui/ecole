<?php

namespace Database\Factories\Timetable;

use App\Enums\Timetable\Weekday;
use App\Models\Grades\ClassSubject;
use App\Models\Timetable\TimetableSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimetableSlot>
 */
class TimetableSlotFactory extends Factory
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
            'weekday' => Weekday::Monday,
            'starts_at' => '08:00',
            'hours' => 1,
            'room_id' => null,
        ];
    }
}
