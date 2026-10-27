<?php

namespace App\Models\Billing;

use App\Enums\Core\GradeLevel;
use App\Models\Core\Enrollment;
use App\Models\Core\SchoolYear;
use Database\Factories\Billing\FeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['school_year_id', 'name', 'level', 'is_optional'])]
class Fee extends Model
{
    /** @use HasFactory<FeeFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => GradeLevel::class,
            'is_optional' => 'boolean',
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
     * @return HasMany<FeeInstallment, $this>
     */
    public function installments(): HasMany
    {
        return $this->hasMany(FeeInstallment::class)->orderBy('due_on');
    }

    /**
     * The students who subscribed to this optional fee.
     *
     * @return BelongsToMany<Enrollment, $this>
     */
    public function enrollments(): BelongsToMany
    {
        return $this->belongsToMany(Enrollment::class);
    }

    /**
     * @return Attribute<int, never>
     */
    protected function amount(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->installments->sum('amount'));
    }

    /**
     * Fees of the school year that apply to a level: those of that level and those of every level.
     *
     * @param  Builder<Fee>  $query
     */
    #[Scope]
    protected function forLevel(Builder $query, int $schoolYearId, GradeLevel $level): void
    {
        $query->where('school_year_id', $schoolYearId)
            ->where(fn (Builder $query) => $query->whereNull('level')->orWhere('level', $level));
    }
}
