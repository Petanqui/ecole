<?php

namespace Database\Factories\Grades;

use App\Models\Core\SchoolYear;
use App\Models\Grades\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Terms are created automatically with their school year: prefer `$schoolYear->terms`
 * over this factory, which only exists for completeness.
 *
 * @extends Factory<Term>
 */
class TermFactory extends Factory
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
            'number' => 4,
            'is_closed' => false,
        ];
    }
}
