<?php

namespace App\Services\Attendance;

use App\Enums\Attendance\AttendanceStatus;
use App\Models\Core\Enrollment;
use App\Models\Core\SchoolClass;
use App\Models\Grades\Term;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Hours of absence (and how many of them are justified) and late arrivals of students,
 * for a term or for the whole school year.
 */
class AttendanceSummary
{
    public function __construct(
        public readonly int $absenceHours = 0,
        public readonly int $justifiedHours = 0,
        public readonly int $lateArrivals = 0,
    ) {}

    public static function forEnrollment(Enrollment $enrollment, ?Term $term = null): self
    {
        return static::forEnrollmentIds([$enrollment->getKey()], $term)->get($enrollment->getKey(), new self);
    }

    /**
     * @return Collection<int, self> keyed by student id, for every student enrolled in the class
     */
    public static function forClass(SchoolClass $schoolClass, Term $term): Collection
    {
        $studentIds = $schoolClass->enrollments()->pluck('student_id', 'id');
        $summaries = static::forEnrollmentIds($studentIds->keys()->all(), $term);

        return $studentIds->mapWithKeys(fn (int $studentId, int $enrollmentId): array => [
            $studentId => $summaries->get($enrollmentId, new self),
        ]);
    }

    /**
     * @param  array<int, int>  $enrollmentIds
     * @return Collection<int, self> keyed by enrollment id, only for students with at least one absence or late arrival
     */
    public static function forEnrollmentIds(array $enrollmentIds, ?Term $term = null): Collection
    {
        $absent = AttendanceStatus::Absent->value;

        return DB::table('attendances')
            ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendances.attendance_session_id')
            ->whereIn('attendances.enrollment_id', $enrollmentIds)
            ->when($term, fn ($query) => $query->where('attendance_sessions.term_id', $term->getKey()))
            ->groupBy('attendances.enrollment_id')
            ->select('attendances.enrollment_id')
            ->selectRaw('sum(case when attendances.status = ? then attendance_sessions.hours else 0 end) as absence_hours', [$absent])
            ->selectRaw('sum(case when attendances.status = ? and attendances.justified_at is not null then attendance_sessions.hours else 0 end) as justified_hours', [$absent])
            ->selectRaw('sum(case when attendances.status = ? then 1 else 0 end) as late_arrivals', [AttendanceStatus::Late->value])
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                (int) $row->enrollment_id => new self((int) $row->absence_hours, (int) $row->justified_hours, (int) $row->late_arrivals),
            ]);
    }

    public function unjustifiedHours(): int
    {
        return $this->absenceHours - $this->justifiedHours;
    }

    /**
     * "6 h d'absence dont 4 h justifiées — 2 retards".
     */
    public function describe(): string
    {
        $absences = $this->absenceHours === 0
            ? 'Aucune absence'
            : "{$this->absenceHours} h d'absence".($this->justifiedHours > 0 ? " dont {$this->justifiedHours} h justifiée(s)" : '');

        $lateArrivals = match ($this->lateArrivals) {
            0 => 'aucun retard',
            1 => '1 retard',
            default => "{$this->lateArrivals} retards",
        };

        return "{$absences} — {$lateArrivals}";
    }
}
