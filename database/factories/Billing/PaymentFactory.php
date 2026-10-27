<?php

namespace Database\Factories\Billing;

use App\Enums\Billing\PaymentMethod;
use App\Models\Billing\Payment;
use App\Models\Core\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
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
            'amount' => fake()->numberBetween(1, 20) * 5000,
            'paid_on' => now(),
            'method' => PaymentMethod::Cash,
            'reference' => null,
        ];
    }
}
