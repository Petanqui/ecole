<?php

namespace App\Models\Grades;

use App\Enums\Grades\AssessmentType;
use App\Models\Core\Enrollment;
use App\Models\User;
use App\Policies\AssessmentPolicy;
use Database\Factories\Grades\AssessmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['class_subject_id', 'term_id', 'type', 'title', 'held_on', 'max_score'])]
#[UsePolicy(AssessmentPolicy::class)]
class Assessment extends Model
{
    /** @use HasFactory<AssessmentFactory> */
    use HasFactory;

    /**
     * Every student enrolled in the class gets a (still empty) mark as soon as the assessment exists.
     */
    protected static function booted(): void
    {
        static::created(function (Assessment $assessment): void {
            $assessment->syncStudents();
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
            'type' => AssessmentType::class,
            'held_on' => 'date',
            'max_score' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ClassSubject, $this>
     */
    public function classSubject(): BelongsTo
    {
        return $this->belongsTo(ClassSubject::class);
    }

    /**
     * @return BelongsTo<Term, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * @return HasMany<Mark, $this>
     */
    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }

    /**
     * Add an empty mark for every student enrolled in the class who does not have one yet,
     * e.g. a student enrolled after the assessment was created.
     *
     * @return int The number of marks added.
     */
    public function syncStudents(): int
    {
        $studentIds = Enrollment::query()
            ->where('school_class_id', $this->classSubject->school_class_id)
            ->whereNotIn('student_id', $this->marks()->select('student_id'))
            ->pluck('student_id');

        foreach ($studentIds as $studentId) {
            $this->marks()->create(['student_id' => $studentId]);
        }

        return $studentIds->count();
    }

    /**
     * Staff see every assessment; a teacher only those of the subjects they teach.
     *
     * @param  Builder<Assessment>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if (! $user->isStaffMember()) {
            $query->whereRelation('classSubject', 'teacher_id', $user->getKey());
        }
    }
}
