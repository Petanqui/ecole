<?php

namespace Database\Factories\Messaging;

use App\Models\Core\Family;
use App\Models\Messaging\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
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
            'student_id' => null,
            'teacher_id' => null,
            'subject' => fake()->sentence(3),
        ];
    }
}
