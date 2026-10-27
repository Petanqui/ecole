<?php

namespace Database\Factories\Billing;

use App\Models\Billing\Fee;
use App\Models\Billing\FeeInstallment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeInstallment>
 */
class FeeInstallmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fee_id' => Fee::factory(),
            'due_on' => fake()->dateTimeBetween('-3 months', '+6 months'),
            'amount' => fake()->numberBetween(1, 20) * 5000,
        ];
    }
}
