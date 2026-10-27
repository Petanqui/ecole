<?php

namespace Database\Factories\Messaging;

use App\Enums\Messaging\AnnouncementAudience;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\Messaging\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
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
            'audience' => AnnouncementAudience::School,
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'author_id' => User::factory(),
        ];
    }

    public function forClass(SchoolClass $schoolClass): static
    {
        return $this->state([
            'audience' => AnnouncementAudience::SchoolClass,
            'school_class_id' => $schoolClass->getKey(),
            'school_year_id' => $schoolClass->school_year_id,
        ]);
    }
}
