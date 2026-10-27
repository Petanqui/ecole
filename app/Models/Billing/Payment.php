<?php

namespace App\Models\Billing;

use App\Enums\Billing\PaymentMethod;
use App\Models\Core\Enrollment;
use App\Models\User;
use Database\Factories\Billing\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

#[Fillable(['enrollment_id', 'amount', 'paid_on', 'method', 'reference', 'payer_name', 'received_by'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            $payment->receipt_number ??= static::nextReceiptNumber((int) now()->year);
        });
    }

    /**
     * Reserve the next receipt number for the given year, e.g. "REC-2026-00001".
     *
     * The per-year counter row is locked while it is incremented, so two payments
     * recorded at the same time can never receive the same number.
     */
    public static function nextReceiptNumber(int $year): string
    {
        $number = DB::transaction(function () use ($year): int {
            DB::table('receipt_sequences')->insertOrIgnore(['year' => $year, 'last_number' => 0]);

            $lastNumber = DB::table('receipt_sequences')
                ->where('year', $year)
                ->lockForUpdate()
                ->value('last_number');

            DB::table('receipt_sequences')
                ->where('year', $year)
                ->update(['last_number' => $lastNumber + 1]);

            return $lastNumber + 1;
        });

        return sprintf('REC-%d-%05d', $year, $number);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_on' => 'date',
            'method' => PaymentMethod::class,
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Enrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function cancel(User $user, string $reason): void
    {
        $this->forceFill([
            'cancelled_at' => now(),
            'cancelled_by' => $user->getKey(),
            'cancellation_reason' => $reason,
        ])->save();
    }

    /**
     * @param  Builder<Payment>  $query
     */
    #[Scope]
    protected function valid(Builder $query): void
    {
        $query->whereNull('cancelled_at');
    }
}
