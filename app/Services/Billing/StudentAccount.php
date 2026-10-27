<?php

namespace App\Services\Billing;

use App\Models\Billing\Fee;
use App\Models\Billing\FeeInstallment;
use App\Models\Core\Enrollment;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * What a student owes for a school year: every installment of their applicable fees, by due date.
 */
class StudentAccount
{
    /**
     * @var Collection<int, array{label: string, due_on: CarbonInterface, amount: int}>
     */
    public readonly Collection $dues;

    public function __construct(public readonly Enrollment $enrollment)
    {
        $this->dues = $enrollment->applicableFees()
            ->flatMap(fn (Fee $fee) => $fee->installments->values()->map(fn (FeeInstallment $installment, int $index): array => [
                'label' => $fee->installments->count() > 1 ? "{$fee->name} — ".static::installmentName($index + 1) : $fee->name,
                'due_on' => $installment->due_on,
                'amount' => $installment->amount,
            ]))
            ->sortBy(fn (array $due): string => $due['due_on']->toDateString())
            ->values();
    }

    public function total(): int
    {
        return $this->dues->sum('amount');
    }

    /**
     * The amount that has fallen due on the given date.
     */
    public function dueBy(CarbonInterface $date): int
    {
        return $this->dues->filter(fn (array $due): bool => $due['due_on']->lte($date))->sum('amount');
    }

    public static function installmentName(int $number): string
    {
        return ($number === 1 ? '1re' : "{$number}e").' tranche';
    }

    /**
     * 150000 → "150 000 FCFA".
     */
    public static function formatAmount(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' FCFA';
    }
}
