<?php

namespace App\Services\Billing;

use App\Enums\Billing\DiscountKind;
use App\Models\Billing\Discount;
use App\Models\Billing\Fee;
use App\Models\Billing\FeeInstallment;
use App\Models\Billing\Payment;
use App\Models\Core\Enrollment;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * What a student owes for a school year: every installment of their applicable fees, by due date,
 * and what they paid. Payments settle the installments in due-date order, oldest first.
 */
class StudentAccount
{
    /**
     * @var Collection<int, array{label: string, due_on: CarbonInterface, discount: int, amount: int, paid: int, remaining: int}>
     */
    public readonly Collection $dues;

    /**
     * Payments that were not cancelled, oldest first.
     *
     * @var Collection<int, Payment>
     */
    public readonly Collection $payments;

    /**
     * @param  Collection<int, Fee>|null  $yearFees  every fee of the enrollment's school year with its installments,
     *                                               to share between accounts; loaded from the database when null
     */
    public function __construct(public readonly Enrollment $enrollment, ?Collection $yearFees = null)
    {
        $this->payments = $enrollment->relationLoaded('payments')
            ? $enrollment->payments->whereNull('cancelled_at')->sortBy([['paid_on', 'asc'], ['id', 'asc']])->values()
            : $enrollment->payments()->valid()->orderBy('paid_on')->orderBy('id')->get();

        $unallocated = $this->payments->sum('amount');

        $fees = $yearFees === null ? $enrollment->applicableFees() : static::applicableAmong($yearFees, $enrollment);
        $discounts = $enrollment->relationLoaded('discounts') ? $enrollment->discounts : $enrollment->discounts()->with('fees')->get();
        $discountByFee = static::discountByFee($fees, $discounts);

        $this->dues = $fees
            ->flatMap(function (Fee $fee) use ($discountByFee): Collection {
                $installmentDiscounts = static::spread($discountByFee[$fee->getKey()] ?? 0, $fee->installments->pluck('amount')->all());

                return $fee->installments->values()->map(fn (FeeInstallment $installment, int $index): array => [
                    'label' => $fee->installments->count() > 1 ? "{$fee->name} — ".static::installmentName($index + 1) : $fee->name,
                    'due_on' => $installment->due_on,
                    'discount' => $installmentDiscounts[$index],
                    'amount' => $installment->amount - $installmentDiscounts[$index],
                ]);
            })
            ->sortBy(fn (array $due): string => $due['due_on']->toDateString())
            ->values()
            ->map(function (array $due) use (&$unallocated): array {
                $paid = min($due['amount'], $unallocated);
                $unallocated -= $paid;

                return [...$due, 'paid' => $paid, 'remaining' => $due['amount'] - $paid];
            });
    }

    /**
     * Accounts of many enrollments at once, with a handful of queries instead of a few per student.
     *
     * @param  EloquentCollection<int, Enrollment>  $enrollments
     * @return Collection<int, StudentAccount> keyed by enrollment id
     */
    public static function forEnrollments(EloquentCollection $enrollments): Collection
    {
        $enrollments->loadMissing(['schoolClass', 'payments', 'optionalFees', 'discounts.fees']);

        $feesByYear = Fee::query()
            ->whereIn('school_year_id', $enrollments->pluck('school_year_id')->unique())
            ->with('installments')
            ->get()
            ->groupBy('school_year_id');

        return $enrollments->mapWithKeys(fn (Enrollment $enrollment): array => [
            $enrollment->getKey() => new static($enrollment, $feesByYear->get($enrollment->school_year_id, collect())),
        ])->toBase();
    }

    /**
     * Same selection as Enrollment::applicableFees(), made in memory.
     *
     * @param  Collection<int, Fee>  $yearFees
     * @return Collection<int, Fee>
     */
    protected static function applicableAmong(Collection $yearFees, Enrollment $enrollment): Collection
    {
        $subscribedFeeIds = $enrollment->optionalFees->modelKeys();

        return $yearFees
            ->filter(fn (Fee $fee): bool => ($fee->level === null || $fee->level === $enrollment->schoolClass->level)
                && (! $fee->is_optional || in_array($fee->getKey(), $subscribedFeeIds, true)))
            ->sortBy([['is_optional', 'asc'], ['name', 'asc']])
            ->values();
    }

    /**
     * The reduction on each fee: a percentage of each fee it names, or a fixed amount shared between
     * those fees in proportion to their amounts. Reductions add up, never beyond the fee.
     *
     * @param  Collection<int, Fee>  $fees  the fees the student owes, with their installments
     * @param  Collection<int, Discount>  $discounts  with their fees
     * @return array<int, int> fee id => reduction
     */
    protected static function discountByFee(Collection $fees, Collection $discounts): array
    {
        $byFee = [];

        foreach ($discounts as $discount) {
            $targets = $fees->whereIn('id', $discount->fees->modelKeys())->values();

            $shares = $discount->kind === DiscountKind::Percentage
                ? $targets->map(fn (Fee $fee): int => intdiv($fee->amount * $discount->value, 100))->all()
                : static::spread($discount->value, $targets->map(fn (Fee $fee): int => $fee->amount)->all());

            foreach ($targets as $index => $fee) {
                $byFee[$fee->getKey()] = min($fee->amount, ($byFee[$fee->getKey()] ?? 0) + $shares[$index]);
            }
        }

        return $byFee;
    }

    /**
     * Share an amount between parts in proportion to their weights, in whole francs, the remainder
     * going to the last part; never more than the total of the weights.
     *
     * @param  list<int>  $weights
     * @return list<int>
     */
    protected static function spread(int $amount, array $weights): array
    {
        $total = array_sum($weights);
        $amount = min($amount, $total);

        if ($weights === [] || $total === 0) {
            return array_map(fn (): int => 0, $weights);
        }

        $shares = array_map(fn (int $weight): int => intdiv($amount * $weight, $total), $weights);
        $shares[array_key_last($shares)] += $amount - array_sum($shares);

        return $shares;
    }

    /**
     * The total of the reductions granted for the year.
     */
    public function discount(): int
    {
        return $this->dues->sum('discount');
    }

    /**
     * The next installment that is not fully paid.
     *
     * @return array{label: string, due_on: CarbonInterface, discount: int, amount: int, paid: int, remaining: int}|null
     */
    public function nextUnpaidDue(): ?array
    {
        return $this->dues->first(fn (array $due): bool => $due['remaining'] > 0);
    }

    public function total(): int
    {
        return $this->dues->sum('amount');
    }

    public function paid(): int
    {
        return $this->payments->sum('amount');
    }

    /**
     * What is left to pay for the year (never below zero).
     */
    public function balance(): int
    {
        return max(0, $this->total() - $this->paid());
    }

    /**
     * The amount that has fallen due on the given date.
     */
    public function dueBy(CarbonInterface $date): int
    {
        return $this->dues->filter(fn (array $due): bool => $due['due_on']->lte($date))->sum('amount');
    }

    /**
     * What should have been paid by the given date and was not.
     */
    public function overdue(CarbonInterface $date): int
    {
        return max(0, $this->dueBy($date) - $this->paid());
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
