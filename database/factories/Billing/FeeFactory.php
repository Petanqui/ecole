<?php

namespace Database\Factories\Billing;

use App\Models\Billing\Fee;
use App\Models\Core\SchoolYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fee>
 */
class FeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_year_id' => SchoolYear::factory(),
            'name' => 'Frais de scolarité',
            'level' => null,
            'is_optional' => false,
        ];
    }

    /**
     * Indicate that students subscribe to the fee one by one (canteen, transport…).
     */
    public function optional(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Cantine',
            'is_optional' => true,
        ]);
    }
}
