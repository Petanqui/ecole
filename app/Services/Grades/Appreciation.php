<?php

namespace App\Services\Grades;

/**
 * Words printed on report cards for an average, using the thresholds of config/grades.php
 * (written on a scale of 20 and scaled to the grading scale of the class).
 */
class Appreciation
{
    /**
     * "Très bien", "Passable"… for a subject average.
     */
    public static function forSubject(?float $average, int $gradingScale): ?string
    {
        if ($average === null) {
            return null;
        }

        return static::firstReached(config('grades.appreciations'), $average, $gradingScale);
    }

    /**
     * "Félicitations", "Tableau d'honneur", "Avertissement"… for a general average, or null.
     */
    public static function distinction(?float $average, int $gradingScale): ?string
    {
        if ($average === null) {
            return null;
        }

        $distinction = static::firstReached(config('grades.distinctions'), $average, $gradingScale);

        if ($distinction !== null) {
            return $distinction;
        }

        return static::onScaleOf20($average, $gradingScale) < config('grades.warning.below')
            ? config('grades.warning.label')
            : null;
    }

    /**
     * @param  array<string, float|int>  $thresholds  label => minimum on a scale of 20, highest first
     */
    protected static function firstReached(array $thresholds, float $average, int $gradingScale): ?string
    {
        $average = static::onScaleOf20($average, $gradingScale);

        foreach ($thresholds as $label => $minimum) {
            if ($average >= $minimum) {
                return $label;
            }
        }

        return null;
    }

    protected static function onScaleOf20(float $average, int $gradingScale): float
    {
        return round($average * 20 / $gradingScale, 2);
    }
}
