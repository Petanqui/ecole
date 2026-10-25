<?php

namespace Database\Factories\Core;

use App\Enums\Core\Gender;
use App\Models\Core\Family;
use App\Models\Core\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement(Gender::cases());

        return [
            'family_id' => Family::factory(),
            'mena_number' => null,
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName($gender === Gender::Male ? 'male' : 'female'),
            'gender' => $gender,
            'birth_date' => fake()->dateTimeBetween('-19 years', '-5 years'),
            'birth_place' => fake()->optional()->city(),
        ];
    }
}
