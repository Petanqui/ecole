<?php

namespace App\Models\Billing;

use Database\Factories\Billing\FeeInstallmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['fee_id', 'due_on', 'amount'])]
class FeeInstallment extends Model
{
    /** @use HasFactory<FeeInstallmentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Fee, $this>
     */
    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class);
    }
}
