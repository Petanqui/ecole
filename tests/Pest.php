<?php

use App\Enums\Core\GradeLevel;
use App\Models\Billing\Fee;
use App\Models\Core\Enrollment;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\Core\Student;
use App\Models\Grades\Assessment;
use App\Models\Grades\ClassSubject;
use App\Models\Grades\Subject;
use App\Models\Grades\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * A class of the given level with enrolled students, and the first term of its school year.
 * Without the SubjectSeeder the class starts with an empty programme, so a test can build
 * exactly the programme it needs with addSubject().
 *
 * @return array{0: SchoolClass, 1: Collection<int, Student>, 2: Term}
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

/**
 * @param  array<string, int>  $installments  due date => amount
 */
function createFee(SchoolYear $schoolYear, ?GradeLevel $level, string $name, array $installments, bool $isOptional = false): Fee
{
    $fee = Fee::factory()->for($schoolYear)->create(['level' => $level, 'name' => $name, 'is_optional' => $isOptional]);

    foreach ($installments as $dueOn => $amount) {
        $fee->installments()->create(['due_on' => $dueOn, 'amount' => $amount]);
    }

    return $fee;
}
