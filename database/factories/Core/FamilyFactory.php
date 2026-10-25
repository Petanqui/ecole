<?php

namespace Database\Factories\Core;

use App\Models\Core\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Family>
 */
class FamilyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Famille '.fake()->lastName(),
            'address' => fake()->optional()->streetAddress(),
            'notes' => null,
        ];
    }
}
