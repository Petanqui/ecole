<?php

namespace Database\Factories\Billing;

use App\Enums\Billing\DiscountKind;
use App\Models\Billing\Discount;
use App\Models\Core\SchoolYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
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
            'name' => 'Fratrie',
            'kind' => DiscountKind::Percentage,
            'value' => 10,
        ];
    }

    public function amount(int $value): static
    {
        return $this->state(['kind' => DiscountKind::Amount, 'value' => $value]);
    }
}
