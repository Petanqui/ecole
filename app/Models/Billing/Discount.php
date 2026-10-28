<?php

namespace App\Models\Billing;

use App\Enums\Billing\DiscountKind;
use App\Models\Core\Enrollment;
use App\Models\Core\SchoolYear;
use App\Services\Billing\StudentAccount;
use Database\Factories\Billing\DiscountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A kind of reduction for a school year ("Fratrie", "Bourse", "Enfant du personnel"): a percentage
 * or a fixed amount off the fees it names, given to the students it is attributed to.
 */
#[Fillable(['school_year_id', 'name', 'kind', 'value'])]
class Discount extends Model
{
    /** @use HasFactory<DiscountFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => DiscountKind::class,
            'value' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<SchoolYear, $this>
     */
    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * The fees the reduction applies to.
     *
     * @return BelongsToMany<Fee, $this>
     */
    public function fees(): BelongsToMany
    {
        return $this->belongsToMany(Fee::class);
    }

    /**
     * @return BelongsToMany<Enrollment, $this>
     */
    public function enrollments(): BelongsToMany
    {
        return $this->belongsToMany(Enrollment::class);
    }

    /**
     * "10 %" or "25 000 FCFA".
     *
     * @return Attribute<string, never>
     */
    protected function valueLabel(): Attribute
    {
        return Attribute::make(get: fn (): string => $this->kind === DiscountKind::Percentage
            ? "{$this->value} %"
            : StudentAccount::formatAmount($this->value));
    }
}
