<?php

use App\Enums\Core\GradeLevel;
use App\Models\Core\Enrollment;
use App\Models\Core\SchoolClass;
use App\Models\Core\Student;
use App\Models\Grades\Assessment;
use App\Models\Grades\ClassSubject;
use App\Models\Grades\Subject;
use App\Models\Grades\Term;
use App\Services\Grades\TermResults;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * No standard subjects are seeded here, so new classes start with an empty programme
 * and each test builds exactly the programme it needs.
 */
function classWithStudents(GradeLevel $level, int $studentCount): array
{
    $schoolClass = SchoolClass::factory()->create(['level' => $level, 'section' => 'A']);
    $students = Student::factory()->count($studentCount)->create()
        ->each(fn (Student $student) => Enrollment::factory()->for($student)->for($schoolClass)->create());

    return [$schoolClass, $students, $schoolClass->schoolYear->terms->first()];
}

function addSubject(SchoolClass $schoolClass, int $coefficient, int $sortOrder = 0): ClassSubject
{
    return $schoolClass->classSubjects()->create([
        'subject_id' => Subject::factory()->create(['sort_order' => $sortOrder])->id,
        'coefficient' => $coefficient,
    ]);
}

/**
 * @param  array<int, float|string|null>  $scores  student id => score, or 'absent'
 */
function giveMarks(ClassSubject $classSubject, Term $term, int $maxScore, array $scores): Assessment
{
    $assessment = Assessment::factory()->for($classSubject)->for($term)->create(['max_score' => $maxScore]);

    foreach ($scores as $studentId => $score) {
        $assessment->marks()->where('student_id', $studentId)->update(
            $score === 'absent' ? ['is_absent' => true, 'score' => null] : ['score' => $score],
        );
    }

    return $assessment;
}

test('secondary averages bring every mark back to 20 and weigh subjects by coefficient', function () {
    [$schoolClass, $students, $term] = classWithStudents(GradeLevel::Sixieme, 3);
    [$a, $b, $c] = $students->modelKeys();
    $maths = addSubject($schoolClass, coefficient: 3, sortOrder: 1);
    $french = addSubject($schoolClass, coefficient: 1, sortOrder: 2);

    giveMarks($maths, $term, 10, [$a => 8, $b => 5, $c => 'absent']);   // 16 and 10 out of 20
    giveMarks($maths, $term, 20, [$a => 12, $b => 14, $c => 'absent']);
    giveMarks($french, $term, 20, [$a => 10, $b => 18, $c => 'absent']);

    $results = new TermResults($schoolClass, $term);

    expect($results->forStudent($students[0]))
        ->subjects->{$maths->id}->average->toBe(14.0)
        ->subjects->{$french->id}->average->toBe(10.0)
        ->average->toBe(13.0)
        ->rank->toBe(2);

    expect($results->forStudent($students[1]))
        ->subjects->{$maths->id}->average->toBe(12.0)
        ->average->toBe(13.5)
        ->rank->toBe(1);

    expect($results->forStudent($students[2]))
        ->average->toBeNull()
        ->rank->toBeNull();

    expect($results->subjectStatistics[$maths->id])->toBe(['average' => 13.0, 'highest' => 14.0, 'lowest' => 12.0])
        ->and($results->classStatistics)->toBe(['average' => 13.25, 'highest' => 13.5, 'lowest' => 13.0])
        ->and($results->rankedCount())->toBe(2)
        ->and($results->students->keys()->all())->toBe([$b, $a, $c]);
});

test('primary averages add up points, so a composition out of 50 weighs more than a mark out of 10', function () {
    [$schoolClass, $students, $term] = classWithStudents(GradeLevel::Cm2, 1);
    $french = addSubject($schoolClass, coefficient: 1);

    giveMarks($french, $term, 10, [$students[0]->id => 6]);
    giveMarks($french, $term, 50, [$students[0]->id => 40]);

    $results = new TermResults($schoolClass, $term);

    // (6 + 40) / (10 + 50) × 10 = 7.666…
    expect($results->gradingScale)->toBe(10)
        ->and($results->forStudent($students[0])['average'])->toBe(7.67);
});

test('a subject without any mark does not count in the general average', function () {
    [$schoolClass, $students, $term] = classWithStudents(GradeLevel::Sixieme, 1);
    $maths = addSubject($schoolClass, coefficient: 3);
    addSubject($schoolClass, coefficient: 2);

    giveMarks($maths, $term, 20, [$students[0]->id => 14]);

    expect((new TermResults($schoolClass, $term))->forStudent($students[0])['average'])->toBe(14.0);
});

test('equal averages share a rank and the next rank is skipped', function () {
    [$schoolClass, $students, $term] = classWithStudents(GradeLevel::Sixieme, 4);
    [$a, $b, $c, $d] = $students->modelKeys();
    $maths = addSubject($schoolClass, coefficient: 1);

    giveMarks($maths, $term, 20, [$a => 15, $b => 12, $c => 15, $d => 9]);

    $results = new TermResults($schoolClass, $term);

    expect(collect([$a, $b, $c, $d])->map(fn (int $id) => $results->students[$id]['rank'])->all())->toBe([1, 3, 1, 4])
        ->and($results->students[$a]['is_tied'])->toBeTrue()
        ->and($results->students[$b]['is_tied'])->toBeFalse();
});

test('marks of another term are ignored', function () {
    [$schoolClass, $students, $firstTerm] = classWithStudents(GradeLevel::Sixieme, 1);
    $secondTerm = $schoolClass->schoolYear->terms[1];
    $maths = addSubject($schoolClass, coefficient: 1);

    giveMarks($maths, $firstTerm, 20, [$students[0]->id => 8]);
    giveMarks($maths, $secondTerm, 20, [$students[0]->id => 18]);

    expect((new TermResults($schoolClass, $firstTerm))->forStudent($students[0])['average'])->toBe(8.0)
        ->and((new TermResults($schoolClass, $secondTerm))->forStudent($students[0])['average'])->toBe(18.0);
});
