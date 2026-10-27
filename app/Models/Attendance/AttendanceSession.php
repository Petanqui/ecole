<?php

namespace App\Models\Attendance;

use App\Enums\Attendance\AttendanceStatus;
use App\Models\Core\Enrollment;
use App\Models\Grades\ClassSubject;
use App\Models\Grades\Term;
use App\Models\User;
use App\Policies\AttendanceSessionPolicy;
use Database\Factories\Attendance\AttendanceSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A roll call: one lesson of a subject in a class, on a day, from a start time and for a number of hours.
 * Only the students who were absent or late have an attendance record.
 */
#[Fillable(['class_subject_id', 'term_id', 'held_on', 'starts_at', 'hours', 'taken_by'])]
#[UsePolicy(AttendanceSessionPolicy::class)]
class AttendanceSession extends Model
{
    /** @use HasFactory<AttendanceSessionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'held_on' => 'date',
            'hours' => 'integer',
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
     * @return BelongsTo<User, $this>
     */
    public function taker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'taken_by');
    }

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * "08:00 – 10:00".
     *
     * @return Attribute<string, never>
     */
    protected function timeSlot(): Attribute
    {
        return Attribute::make(get: function (): string {
            $start = $this->held_on->copy()->setTimeFromTimeString($this->starts_at);

            return $start->format('H:i').' – '.$start->addHours($this->hours)->format('H:i');
        });
    }

    /**
     * Record where the student was. Marking them present removes their record; a justification
     * already given survives a change between absent and late.
     */
    public function mark(Enrollment $enrollment, AttendanceStatus $status): void
    {
        if ($status === AttendanceStatus::Present) {
            $this->attendances()->where('enrollment_id', $enrollment->getKey())->delete();

            return;
        }

        $this->attendances()->updateOrCreate(['enrollment_id' => $enrollment->getKey()], ['status' => $status]);
    }

    /**
     * Whether another roll call of the same class overlaps this time slot on that day.
     */
    public static function overlapsAnother(int $schoolClassId, string $heldOn, string $startsAt, int $hours, ?int $ignoredId = null): bool
    {
        $start = strtotime($startsAt);
        $end = $start + $hours * 3600;

        return static::query()
            ->whereRelation('classSubject', 'school_class_id', $schoolClassId)
            ->whereDate('held_on', $heldOn)
            ->when($ignoredId, fn (Builder $query) => $query->whereKeyNot($ignoredId))
            ->get(['starts_at', 'hours'])
            ->contains(function (AttendanceSession $other) use ($start, $end): bool {
                $otherStart = strtotime($other->starts_at);

                return $otherStart < $end && $start < $otherStart + $other->hours * 3600;
            });
    }

    /**
     * Staff see every roll call; a teacher only those of the subjects they teach.
     *
     * @param  Builder<AttendanceSession>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if (! $user->isStaffMember()) {
            $query->whereRelation('classSubject', 'teacher_id', $user->getKey());
        }
    }
}
