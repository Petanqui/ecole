<?php

namespace App\Models\Grades;

use App\Models\Core\SchoolYear;
use Database\Factories\Grades\TermFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['school_year_id', 'number', 'is_closed'])]
class Term extends Model
{
    /** @use HasFactory<TermFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'is_closed' => 'boolean',
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
     * The terms of the school year that are not closed, first one first.
     *
     * @return array<int, string>
     */
    public static function openOptions(int $schoolYearId): array
    {
        return static::query()
            ->where('school_year_id', $schoolYearId)
            ->where('is_closed', false)
            ->orderBy('number')
            ->get()
            ->mapWithKeys(fn (Term $term): array => [$term->id => $term->name])
            ->all();
    }

    /**
     * "1er trimestre", "2e trimestre", "3e trimestre".
     *
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): string => ($this->number === 1 ? '1er' : "{$this->number}e").' trimestre',
        );
    }
}
