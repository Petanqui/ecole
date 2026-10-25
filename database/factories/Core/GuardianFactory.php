<?php

namespace Database\Factories\Core;

use App\Enums\Core\GuardianRelationship;
use App\Models\Core\Family;
use App\Models\Core\Guardian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guardian>
 */
class GuardianFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'relationship' => fake()->randomElement(GuardianRelationship::cases()),
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'phone' => '+22507'.fake()->numerify('########'),
            'email' => fake()->optional()->safeEmail(),
            'profession' => fake()->optional()->jobTitle(),
        ];
    }
}
