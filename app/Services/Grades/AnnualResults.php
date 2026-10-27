<?php

namespace App\Services\Grades;

use App\Enums\Core\GradeLevel;
use App\Models\Core\SchoolClass;
use App\Models\Grades\Term;

/**
 * Annual averages and ranks of a class: sum(term average × weight) / sum(weights) over the
 * terms where the student has an average, with the weights of config/grades.php.
 * From CP1 to CM1, is_promoted tells whether the annual average reaches the passing mark.
 */
class AnnualResults
{
    /**
     * Keyed by student id.
     *
     * @var array<int, array{terms: array<int, ?float>, average: ?float, rank: ?int, is_tied: bool, is_promoted: ?bool}>
     */
    public readonly array $students;

    /**
     * @var array{average: ?float, highest: ?float, lowest: ?float}
     */
    public readonly array $classStatistics;

    public function __construct(public readonly SchoolClass $schoolClass)
    {
        $weights = config('grades.annual_term_weights.'.($schoolClass->level->isPrimary() ? 'primary' : 'secondary'));
        $passingAverage = $schoolClass->level->isPrimary() && $schoolClass->level !== GradeLevel::Cm2
            ? config('grades.primary_passing_average')
            : null;

        $termResults = $schoolClass->schoolYear->terms
            ->mapWithKeys(fn (Term $term): array => [$term->number => new TermResults($schoolClass, $term)]);

        $students = [];

        foreach ($termResults->first()?->students ?? [] as $studentId => $row) {
            $termAverages = $termResults->map(fn (TermResults $results): ?float => $results->students[$studentId]['average'] ?? null)->all();

            $weightedSum = 0.0;
            $weightTotal = 0;

            foreach ($termAverages as $number => $average) {
                if ($average !== null) {
                    $weightedSum += $average * ($weights[$number] ?? 1);
                    $weightTotal += $weights[$number] ?? 1;
                }
            }

            $average = $weightTotal > 0 ? round($weightedSum / $weightTotal, 2) : null;

            $students[$studentId] = [
                'terms' => $termAverages,
                'average' => $average,
                'rank' => null,
                'is_tied' => false,
                'is_promoted' => $passingAverage === null || $average === null ? null : $average >= $passingAverage,
            ];
        }

        $ranking = TermResults::rank(collect($students)->map(fn (array $student): ?float => $student['average']));

        foreach ($students as $studentId => $student) {
            $students[$studentId]['rank'] = $ranking['ranks'][$studentId] ?? null;
            $students[$studentId]['is_tied'] = in_array($students[$studentId]['rank'], $ranking['tied'], true);
        }

        $this->students = $students;
        $this->classStatistics = TermResults::statistics(collect($students)->map(fn (array $student): ?float => $student['average']));
    }
}
