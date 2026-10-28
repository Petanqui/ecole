<?php

namespace App\Services\Billing;

use App\Enums\Billing\PaymentMethod;
use App\Models\Billing\Payment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The payments recorded over a period (by payment date), optionally for one cashier or one payment method,
 * with what was collected by method and by cashier. Cancelled payments are listed but never counted.
 */
class CashReport
{
    /**
     * Every payment of the period, cancelled ones included, oldest first.
     *
     * @var Collection<int, Payment>
     */
    public readonly Collection $payments;

    public function __construct(
        public readonly CarbonInterface $from,
        public readonly CarbonInterface $until,
        public readonly ?int $receiverId = null,
        public readonly ?PaymentMethod $method = null,
    ) {
        $this->payments = $this->query()
            ->with(['enrollment.student', 'enrollment.schoolClass', 'receiver'])
            ->orderBy('paid_on')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Builder<Payment>
     */
    public function query(): Builder
    {
        return Payment::query()
            ->whereDate('paid_on', '>=', $this->from)
            ->whereDate('paid_on', '<=', $this->until)
            ->when($this->receiverId, fn (Builder $query, int $receiverId) => $query->where('received_by', $receiverId))
            ->when($this->method, fn (Builder $query, PaymentMethod $method) => $query->where('method', $method));
    }

    /**
     * @return Collection<int, Payment>
     */
    public function validPayments(): Collection
    {
        return $this->payments->reject(fn (Payment $payment): bool => $payment->isCancelled())->values();
    }

    /**
     * @return Collection<int, Payment>
     */
    public function cancelledPayments(): Collection
    {
        return $this->payments->filter(fn (Payment $payment): bool => $payment->isCancelled())->values();
    }

    public function total(): int
    {
        return $this->validPayments()->sum('amount');
    }

    /**
     * What was collected with each payment method used, in the order of the methods.
     *
     * @return Collection<int, array{method: PaymentMethod, count: int, total: int}>
     */
    public function byMethod(): Collection
    {
        $payments = $this->validPayments();

        return collect(PaymentMethod::cases())
            ->map(fn (PaymentMethod $method): array => [
                'method' => $method,
                'count' => $payments->where('method', $method)->count(),
                'total' => $payments->where('method', $method)->sum('amount'),
            ])
            ->filter(fn (array $row): bool => $row['count'] > 0)
            ->values();
    }

    /**
     * What each cashier collected, splitting the cash they should hand over from the rest.
     *
     * @return Collection<int, array{cashier: string, count: int, cash: int, other: int, total: int}>
     */
    public function byCashier(): Collection
    {
        return $this->validPayments()
            ->groupBy(fn (Payment $payment): int => $payment->received_by ?? 0)
            ->map(function (Collection $payments): array {
                $cash = $payments->where('method', PaymentMethod::Cash)->sum('amount');
                $total = $payments->sum('amount');

                return [
                    'cashier' => $payments->first()->receiver?->name ?? 'Compte supprimé',
                    'count' => $payments->count(),
                    'cash' => $cash,
                    'other' => $total - $cash,
                    'total' => $total,
                ];
            })
            ->sortBy('cashier')
            ->values();
    }

    /**
     * "du 01/10/2026 au 31/10/2026", or "du 28/10/2026" for a single day.
     */
    public function periodLabel(): string
    {
        return $this->from->isSameDay($this->until)
            ? 'du '.$this->from->format('d/m/Y')
            : 'du '.$this->from->format('d/m/Y').' au '.$this->until->format('d/m/Y');
    }

    /**
     * The users who ever recorded a payment, for choosing a cashier.
     *
     * @return array<int, string>
     */
    public static function cashierOptions(): array
    {
        return User::query()
            ->whereIn('id', Payment::query()->select('received_by')->whereNotNull('received_by'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
