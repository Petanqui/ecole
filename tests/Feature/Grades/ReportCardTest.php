<?php

use App\Enums\Core\GradeLevel;
use App\Models\Core\Guardian;
use App\Models\Core\SchoolYear;
use App\Models\Core\Student;
use App\Models\User;
use App\Services\Grades\AnnualResults;
use App\Services\Grades\TermResults;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    [$this->schoolClass, $this->students, $this->firstTerm] = classWithStudents(GradeLevel::Sixieme, 2);
    $this->maths = addSubject($this->schoolClass, coefficient: 3);

    [$this->awa, $this->yao] = $this->students->all();
    giveMarks($this->maths, $this->firstTerm, 20, [$this->awa->id => 15, $this->yao->id => 9]);
});

function classReportCardUrl(array $parameters): string
{
    return route('filament.gestion.report-cards.class', $parameters);
}

function childReportCardUrl(Student $student, $term): string
{
    return route('filament.portail.report-cards.student', ['student' => $student, 'term' => $term]);
}

test('the annual average weighs terms 2 and 3 double', function () {
    [$term1, $term2, $term3] = $this->schoolClass->schoolYear->terms->all();
    giveMarks($this->maths, $term2, 20, [$this->awa->id => 12]);
    giveMarks($this->maths, $term3, 20, [$this->awa->id => 14, $this->yao->id => 16]);

    $annual = new AnnualResults($this->schoolClass);

    // Awa: (15 + 2×12 + 2×14) / 5 = 13.4 ; Yao has no average in term 2: (9 + 2×16) / 3 = 13.67
    expect($annual->students[$this->awa->id])
        ->terms->toBe([1 => 15.0, 2 => 12.0, 3 => 14.0])
        ->average->toBe(13.4)
        ->rank->toBe(2)
        ->and($annual->students[$this->yao->id])
        ->average->toBe(13.67)
        ->rank->toBe(1);
});

test('staff print the report cards of a whole class as a PDF', function () {
    $this->actingAs(User::factory()->create());

    $this->get(classReportCardUrl(['schoolClass' => $this->schoolClass, 'term' => $this->firstTerm]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('staff print the report card of a single student', function () {
    $this->actingAs(User::factory()->create());

    $this->get(classReportCardUrl(['schoolClass' => $this->schoolClass, 'term' => $this->firstTerm, 'student' => $this->awa->id]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('the report card shows averages, ranks, appreciations and the distinction', function () {
    $results = new TermResults($this->schoolClass, $this->firstTerm);

    $html = view('grades.report-card', [
        'results' => $results,
        'annual' => null,
        'rows' => $results->students->only([$this->awa->id]),
        'schoolClass' => $this->schoolClass,
        'term' => $this->firstTerm,
    ])->render();

    expect($html)
        ->toContain($this->awa->full_name)
        ->toContain('BULLETIN DE NOTES — 1ER TRIMESTRE')
        ->toContain('15,00')
        ->toContain('45,00')
        ->toContain('1er')
        ->toContain('Bien')
        ->toContain('Encouragements')
        ->not->toContain($this->yao->full_name);
});

test('a term of another school year is refused', function () {
    $this->actingAs(User::factory()->create());
    $otherTerm = SchoolYear::factory()->create()->terms->first();

    $this->get(classReportCardUrl(['schoolClass' => $this->schoolClass, 'term' => $otherTerm]))->assertNotFound();
});

test('teachers cannot print class report cards', function () {
    $this->actingAs(User::factory()->teacher()->create());

    $this->get(classReportCardUrl(['schoolClass' => $this->schoolClass, 'term' => $this->firstTerm]))->assertForbidden();
});

test('a parent downloads the report card of their child once the term is closed', function () {
    $parent = User::factory()->guardian()->create();
    Guardian::factory()->for($this->awa->family)->for($parent)->create();
    $this->actingAs($parent);

    $this->get(childReportCardUrl($this->awa, $this->firstTerm))->assertForbidden();

    $this->firstTerm->update(['is_closed' => true]);

    $this->get(childReportCardUrl($this->awa, $this->firstTerm))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('a parent cannot download the report card of another child', function () {
    $parent = User::factory()->guardian()->create();
    Guardian::factory()->for($this->awa->family)->for($parent)->create();
    $this->firstTerm->update(['is_closed' => true]);

    $this->actingAs($parent)->get(childReportCardUrl($this->yao, $this->firstTerm))->assertForbidden();
});
