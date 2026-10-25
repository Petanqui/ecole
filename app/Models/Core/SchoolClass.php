<?php

namespace App\Models\Core;

use App\Enums\Core\GradeLevel;
use App\Models\Grades\ClassSubject;
use App\Models\Grades\Subject;
use Database\Factories\Core\SchoolClassFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['school_year_id', 'level', 'section', 'capacity'])]
class SchoolClass extends Model
{
    /** @use HasFactory<SchoolClassFactory> */
    use HasFactory;

    /**
     * The name is always derived from the level and the section, e.g. "6e" + "1" = "6e1".
     */
    protected static function booted(): void
    {
        static::saving(function (SchoolClass $schoolClass): void {
            $schoolClass->name = $schoolClass->level->className($schoolClass->section);
        });

        static::created(function (SchoolClass $schoolClass): void {
            $schoolClass->applyDefaultProgramme();
        });
    }

    /**
     * Add the subjects of the default programme of the class level that the class does not
     * have yet. Subjects already in the programme keep their coefficient and teacher.
     *
     * @return int The number of subjects added.
     */
    public function applyDefaultProgramme(): int
    {
        $programme = $this->level->defaultProgramme($this->section);
        $existingSubjectIds = $this->classSubjects()->pluck('subject_id');

        $subjects = Subject::query()
            ->whereIn('code', array_keys($programme))
            ->whereNotIn('id', $existingSubjectIds)
            ->get();

        foreach ($subjects as $subject) {
            $this->classSubjects()->create([
                'subject_id' => $subject->id,
                'coefficient' => $programme[$subject->code],
            ]);
        }

        return $subjects->count();
    }

    /**
     * @return HasMany<ClassSubject, $this>
     */
    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class);
    }

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
