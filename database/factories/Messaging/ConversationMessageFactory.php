<?php

namespace Database\Factories\Messaging;

use App\Models\Messaging\Conversation;
use App\Models\Messaging\ConversationMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConversationMessage>
 */
class ConversationMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'author_id' => User::factory(),
            'body' => fake()->paragraph(),
        ];
    }
}
