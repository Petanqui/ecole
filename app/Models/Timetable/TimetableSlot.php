<?php

namespace App\Models\Timetable;

use App\Enums\Timetable\Weekday;
use App\Models\Grades\ClassSubject;
use Database\Factories\Timetable\TimetableSlotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A weekly lesson: a subject of a class (and so its teacher), on a school day, from a start time
 * for a number of hours, in a room. The same week repeats all year.
 */
#[Fillable(['class_subject_id', 'weekday', 'starts_at', 'hours', 'room_id'])]
class TimetableSlot extends Model
{
    /** @use HasFactory<TimetableSlotFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekday' => Weekday::class,
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
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * "08:00 – 10:00".
     *
     * @return Attribute<string, never>
     */
    protected function timeSlot(): Attribute
    {
        return Attribute::make(get: function (): string {
            $start = static::minutes($this->starts_at);

            return static::formatMinutes($start).' – '.static::formatMinutes($start + $this->hours * 60);
        });
    }

    /**
     * Why a lesson cannot take place at that time: the class, the teacher or the room already has a lesson then.
     *
     * @return list<string> empty when the slot is free
     */
    public static function conflicts(ClassSubject $classSubject, Weekday $weekday, string $startsAt, int $hours, ?int $roomId, ?int $ignoredId = null): array
    {
        $start = static::minutes($startsAt);
        $end = $start + $hours * 60;

        return static::query()
            ->where('weekday', $weekday)
            ->whereRelation('classSubject.schoolClass', 'school_year_id', $classSubject->schoolClass->school_year_id)
            ->when($ignoredId, fn ($query) => $query->whereKeyNot($ignoredId))
            ->with(['classSubject.schoolClass', 'classSubject.subject', 'classSubject.teacher', 'room'])
            ->get()
            ->filter(fn (TimetableSlot $other): bool => static::minutes($other->starts_at) < $end
                && $start < static::minutes($other->starts_at) + $other->hours * 60)
            ->flatMap(function (TimetableSlot $other) use ($classSubject, $roomId): array {
                $otherStart = static::minutes($other->starts_at);
                $when = 'le '.mb_strtolower($other->weekday->getLabel()).' de '.static::formatMinutes($otherStart).' à '.static::formatMinutes($otherStart + $other->hours * 60);
                $lesson = "{$other->classSubject->subject->name} en {$other->classSubject->schoolClass->name}";

                return array_filter([
                    $other->classSubject->school_class_id === $classSubject->school_class_id
                        ? "La classe {$classSubject->schoolClass->name} a déjà {$other->classSubject->subject->name} {$when}."
                        : null,
                    $classSubject->teacher_id !== null && $other->classSubject->teacher_id === $classSubject->teacher_id
                        ? "{$classSubject->teacher->name} a déjà {$lesson} {$when}."
                        : null,
                    $roomId !== null && $other->room_id === $roomId
                        ? "La salle {$other->room->name} est déjà prise par {$lesson} {$when}."
                        : null,
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * "08:00" or "08:00:00" → 480.
     */
    public static function minutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);

        return (int) $hours * 60 + (int) $minutes;
    }

    protected static function formatMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
