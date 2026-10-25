<?php

namespace App\Models\Core;

use App\Enums\Core\GradeLevel;
use Database\Factories\Core\SchoolClassFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['school_year_id', 'level', 'name', 'capacity'])]
class SchoolClass extends Model
{
    /** @use HasFactory<SchoolClassFactory> */
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
            'capacity' => 'integer',
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
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Whether the class has reached its capacity (a class without capacity is never full).
     */
    public function isFull(): bool
    {
        return $this->capacity !== null && $this->enrollments()->count() >= $this->capacity;
    }
}
