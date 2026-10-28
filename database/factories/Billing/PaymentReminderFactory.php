<?php

namespace Database\Factories\Billing;

use App\Models\Billing\PaymentReminder;
use App\Models\Core\Enrollment;
use App\Models\Messaging\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentReminder>
 */
class PaymentReminderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'conversation_id' => Conversation::factory(),
            'sent_by' => null,
            'overdue' => 50000,
            'as_of' => now()->toDateString(),
        ];
    }
}
