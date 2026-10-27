<?php

namespace App\Services\Grades;

use App\Models\Core\Enrollment;
use App\Models\Core\SchoolClass;
use App\Models\Core\Student;
use App\Models\Grades\ClassSubject;
use App\Models\Grades\Mark;
use App\Models\Grades\Term;
use Illuminate\Support\Collection;

/**
 * Averages and ranks of one class for one term.
 *
 * Subject average, on the grading scale of the level (10 in primary, 20 in secondary):
 *   - secondary: every mark is brought back to the scale, then a plain mean is taken;
 *   - primary: total points / total maximum scores, brought back to the scale.
 * General average: sum(subject average × coefficient) / sum(coefficients), over the subjects
 * where the student has at least one mark. Absent students and empty marks are ignored.
 * Ranks use standard competition ranking on averages rounded to 2 decimals (1, 2, 2, 4).
 */
class TermResults
{
    /**
     * The subjects of the class, in bulletin order.
     *
     * @var Collection<int, ClassSubject>
     */
    public readonly Collection $subjects;

    /**
     * One row per enrolled student, keyed by student id, ordered by rank then name.
     *
     * @var Collection<int, array{student: Student, subjects: array<int, array{average: ?float, rank: ?int}>, average: ?float, rank: ?int, is_tied: bool}>
     */
    public readonly Collection $students;

    /**
     * Class average, highest and lowest average per class subject id.
     *
     * @var array<int, array{average: ?float, highest: ?float, lowest: ?float}>
     */
    public readonly array $subjectStatistics;

    /**
     * @var array{average: ?float, highest: ?float, lowest: ?float}
     */
    public readonly array $classStatistics;

    public readonly int $gradingScale;

    public function __construct(public readonly SchoolClass $schoolClass, public readonly Term $term)
    {
        $this->gradingScale = $schoolClass->level->gradingScale();

        $this->subjects = $schoolClass->classSubjects()
            ->with('subject')
            ->get()
            ->sortBy('subject.sort_order')
            ->values();

        $students = Student::query()
            ->whereIn('id', Enrollment::query()->where('school_class_id', $schoolClass->getKey())->select('student_id'))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $subjectAverages = $this->subjectAverages();

        $rows = $students->mapWithKeys(fn (Student $student): array => [
            $student->getKey() => [
                'student' => $student,
                'subjects' => $this->subjects->mapWithKeys(fn (ClassSubject $classSubject): array => [
                    $classSubject->getKey() => [
                        'average' => $subjectAverages[$classSubject->getKey()][$student->getKey()] ?? null,
                        'rank' => null,
                    ],
                ])->all(),
                'average' => null,
                'rank' => null,
                'is_tied' => false,
            ],
        ]);

        $rows = $rows->map(function (array $row): array {
            $row['average'] = $this->generalAverage($row['subjects']);

            return $row;
        });

        $generalRanks = static::rank($rows->map(fn (array $row): ?float => $row['average']));

        $subjectRanks = $this->subjects->mapWithKeys(fn (ClassSubject $classSubject): array => [
            $classSubject->getKey() => static::rank($rows->map(fn (array $row): ?float => $row['subjects'][$classSubject->getKey()]['average'])),
        ]);

        $this->students = $rows
            ->map(function (array $row, int $studentId) use ($generalRanks, $subjectRanks): array {
                $row['rank'] = $generalRanks['ranks'][$studentId] ?? null;
                $row['is_tied'] = in_array($row['rank'], $generalRanks['tied'], true);

                foreach ($row['subjects'] as $classSubjectId => $subject) {
                    $row['subjects'][$classSubjectId]['rank'] = $subjectRanks[$classSubjectId]['ranks'][$studentId] ?? null;
                }

                return $row;
            })
            ->sortBy(fn (array $row): array => [$row['rank'] ?? PHP_INT_MAX, $row['student']->last_name, $row['student']->first_name]);

        $this->subjectStatistics = $this->subjects->mapWithKeys(fn (ClassSubject $classSubject): array => [
            $classSubject->getKey() => static::statistics($this->students->map(fn (array $row): ?float => $row['subjects'][$classSubject->getKey()]['average'])),
        ])->all();

        $this->classStatistics = static::statistics($this->students->map(fn (array $row): ?float => $row['average']));
    }

    /**
     * The result row of one student, or null when the student is not enrolled in the class.
     *
     * @return array{student: Student, subjects: array<int, array{average: ?float, rank: ?int}>, average: ?float, rank: ?int, is_tied: bool}|null
     */
    public function forStudent(Student $student): ?array
    {
        return $this->students->get($student->getKey());
    }

    /**
     * "1er", "2e", "2e ex æquo"…
     */
    public static function formatRank(int $rank, bool $isTied = false): string
    {
        return ($rank === 1 ? '1er' : "{$rank}e").($isTied ? ' ex æquo' : '');
    }

    /**
     * French formatting with two decimals: 13.5 => "13,50".
     */
    public static function formatAverage(?float $average): ?string
    {
        return $average === null ? null : number_format($average, 2, ',', ' ');
    }

    /**
     * Number of students with a general average, i.e. who are ranked.
     */
    public function rankedCount(): int
    {
        return $this->students->whereNotNull('average')->count();
    }

    /**
     * Subject averages, keyed by class subject id then student id.
     *
     * @return array<int, array<int, float>>
     */
    protected function subjectAverages(): array
    {
        $marks = Mark::query()
            ->whereNotNull('score')
            ->whereHas('assessment', fn ($query) => $query
                ->where('term_id', $this->term->getKey())
                ->whereIn('class_subject_id', $this->subjects->modelKeys()))
            ->with('assessment:id,class_subject_id,max_score')
            ->get();

        $byPoints = $this->schoolClass->level->weightsMarksByMaxScore();

        return $marks
            ->groupBy(fn (Mark $mark): int => $mark->assessment->class_subject_id)
            ->map(fn (Collection $subjectMarks): array => $subjectMarks
                ->groupBy('student_id')
                ->map(fn (Collection $studentMarks): float => round($byPoints
                    ? $studentMarks->sum('score') / $studentMarks->sum('assessment.max_score') * $this->gradingScale
                    : $studentMarks->avg(fn (Mark $mark): float => $mark->score / $mark->assessment->max_score * $this->gradingScale), 2))
                ->all())
            ->all();
    }

    /**
     * @param  array<int, array{average: ?float, rank: ?int}>  $subjects
     */
    protected function generalAverage(array $subjects): ?float
    {
        $weightedSum = 0.0;
        $coefficients = 0;

        foreach ($this->subjects as $classSubject) {
            $average = $subjects[$classSubject->getKey()]['average'];

            if ($average !== null) {
                $weightedSum += $average * $classSubject->coefficient;
                $coefficients += $classSubject->coefficient;
            }
        }

        return $coefficients > 0 ? round($weightedSum / $coefficients, 2) : null;
    }

    /**
     * Competition ranking: equal averages share a rank and the next rank is skipped (1, 2, 2, 4).
     *
     * @param  Collection<int, ?float>  $averages  keyed by student id
     * @return array{ranks: array<int, int>, tied: array<int, int>}
     */
    protected static function rank(Collection $averages): array
    {
        $sorted = $averages->filter(fn (?float $average): bool => $average !== null)->sortDesc();

        $ranks = [];
        $previousAverage = null;
        $previousRank = 0;
        $position = 0;

        foreach ($sorted as $studentId => $average) {
            $position++;
            $rank = $average === $previousAverage ? $previousRank : $position;
            $ranks[$studentId] = $rank;
            $previousAverage = $average;
            $previousRank = $rank;
        }

        $tied = collect($ranks)->countBy()->filter(fn (int $count): bool => $count > 1)->keys()->all();

        return ['ranks' => $ranks, 'tied' => $tied];
    }

    /**
     * @param  Collection<int, ?float>  $averages
     * @return array{average: ?float, highest: ?float, lowest: ?float}
     */
    protected static function statistics(Collection $averages): array
    {
        $averages = $averages->filter(fn (?float $average): bool => $average !== null);

        return [
            'average' => $averages->isEmpty() ? null : round($averages->avg(), 2),
            'highest' => $averages->max(),
            'lowest' => $averages->min(),
        ];
    }
}
