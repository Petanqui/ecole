<?php

namespace Database\Factories\Core;

use App\Models\Core\SchoolYear;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<SchoolYear>
 */
class SchoolYearFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = fake()->unique()->numberBetween(2000, 2099);

        return [
            'name' => $startYear.'-'.($startYear + 1),
            'starts_on' => Carbon::create($startYear, 9, 1),
            'ends_on' => Carbon::create($startYear + 1, 7, 31),
            'is_current' => false,
        ];
    }

    /**
     * Indicate that the school year is the current one.
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => true,
        ]);
    }
}
