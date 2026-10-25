<?php

namespace App\Models\Core;

use App\Models\Grades\Term;
use Database\Factories\Core\SchoolYearFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'starts_on', 'ends_on', 'is_current'])]
class SchoolYear extends Model
{
    /** @use HasFactory<SchoolYearFactory> */
    use HasFactory;

    /**
     * Only one school year can be the current one: marking a year as current unmarks the others.
     */
    protected static function booted(): void
    {
        static::created(function (SchoolYear $schoolYear): void {
            foreach ([1, 2, 3] as $number) {
                $schoolYear->terms()->create(['number' => $number]);
            }
        });

        static::saved(function (SchoolYear $schoolYear): void {
            if ($schoolYear->is_current && ($schoolYear->wasRecentlyCreated || $schoolYear->wasChanged('is_current'))) {
                static::query()
                    ->whereKeyNot($schoolYear->getKey())
                    ->where('is_current', true)
                    ->update(['is_current' => false]);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_current' => 'boolean',
        ];
    }

    /**
     * @return HasMany<SchoolClass, $this>
     */
    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    /**
     * The three terms of the year, created with it.
     *
     * @return HasMany<Term, $this>
     */
    public function terms(): HasMany
    {
        return $this->hasMany(Term::class)->orderBy('number');
    }

    /**
     * @param  Builder<SchoolYear>  $query
     */
    #[Scope]
    protected function current(Builder $query): void
    {
        $query->where('is_current', true);
    }
}
