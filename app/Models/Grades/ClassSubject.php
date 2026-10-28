<?php

namespace App\Models\Grades;

use App\Models\Core\SchoolClass;
use App\Models\Timetable\TimetableSlot;
use App\Models\User;
use Database\Factories\Grades\ClassSubjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A subject in the programme of a class, with its coefficient and teacher.
 */
#[Fillable(['school_class_id', 'subject_id', 'coefficient', 'teacher_id'])]
class ClassSubject extends Model
{
    /** @use HasFactory<ClassSubjectFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'coefficient' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<SchoolClass, $this>
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * The weekly lessons of this subject in the class.
     *
     * @return HasMany<TimetableSlot, $this>
     */
    public function timetableSlots(): HasMany
    {
        return $this->hasMany(TimetableSlot::class);
    }

    /**
     * "6e1 — Mathématiques" for the subjects of current-year classes, by class then programme order,
     * limited to their own subjects for a teacher.
     *
     * @return array<int, string>
     */
    public static function currentYearOptionsFor(User $user): array
    {
        return static::query()
            ->with(['schoolClass', 'subject'])
            ->whereRelation('schoolClass.schoolYear', 'is_current', true)
            ->when(! $user->isStaffMember(), fn ($query) => $query->where('teacher_id', $user->getKey()))
            ->get()
            ->sortBy(fn (ClassSubject $classSubject): string => $classSubject->schoolClass->name.' '.str_pad((string) $classSubject->subject->sort_order, 3, '0', STR_PAD_LEFT))
            ->mapWithKeys(fn (ClassSubject $classSubject): array => [
                $classSubject->id => "{$classSubject->schoolClass->name} — {$classSubject->subject->name}",
            ])
            ->all();
    }
}
