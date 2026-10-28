<?php

namespace Database\Factories\Timetable;

use App\Models\Timetable\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Salle '.fake()->unique()->numberBetween(1, 999),
            'capacity' => null,
        ];
    }
}
