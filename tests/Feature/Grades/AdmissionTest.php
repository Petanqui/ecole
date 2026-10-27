<?php

use App\Enums\Core\GradeLevel;
use App\Enums\Grades\AdmissionExam;
use App\Filament\Gestion\Pages\Grades\AdmissionPage;
use App\Models\Core\Enrollment;
use App\Models\Core\SchoolYear;
use App\Models\User;
use App\Services\Grades\AnnualResults;
use App\Services\Grades\TermResults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    SchoolYear::query()->update(['is_current' => false]);
    [$this->schoolClass, $students, $this->firstTerm] = classWithStudents(GradeLevel::Cm2, 2);
    $this->schoolClass->schoolYear->update(['is_current' => true]);
    [$this->awa, $this->yao] = $students->all();
    $this->awaEnrollment = Enrollment::where('student_id', $this->awa->id)->sole();
    $this->yaoEnrollment = Enrollment::where('student_id', $this->yao->id)->sole();
});

/**
 * @param  array<int, float>  $scores  in the order of AdmissionExam::cases()
 */
function giveAdmissionScores(Enrollment $enrollment, array $scores): void
{
    foreach (AdmissionExam::cases() as $index => $exam) {
        $enrollment->setAdmissionExamScore($exam, $scores[$index]);
    }
}

test('the MAP is the mean of the four exams and admits from 10 out of 20', function () {
    giveAdmissionScores($this->awaEnrollment, [12, 9, 10, 11]);
    giveAdmissionScores($this->yaoEnrollment, [9, 10, 9.5, 11]);
    $french = addSubject($this->schoolClass, coefficient: 1);
    giveMarks($french, $this->firstTerm, 10, [$this->awa->id => 6, $this->yao->id => 7]);

    $annual = new AnnualResults($this->schoolClass);

    // Awa: 42 / 4 = 10.5 ; Yao: 39.5 / 4 = 9.88
    expect($annual->students[$this->awa->id])
        ->admission_average->toBe(10.5)
        ->is_promoted->toBeTrue()
        ->and($annual->students[$this->yao->id])
        ->admission_average->toBe(9.88)
        ->is_promoted->toBeFalse();
});

test('no MAP or decision until all four exams are entered', function () {
    $this->awaEnrollment->setAdmissionExamScore(AdmissionExam::RegionalComposition1, 15);

    expect($this->awaEnrollment->admissionAverage())->toBeNull()
        ->and((new AnnualResults($this->schoolClass))->students[$this->awa->id]['is_promoted'])->toBeNull();
});

test('clearing a score removes it', function () {
    $this->awaEnrollment->setAdmissionExamScore(AdmissionExam::MockExam1, 12);
    $this->awaEnrollment->setAdmissionExamScore(AdmissionExam::MockExam1, null);

    expect($this->awaEnrollment->admissionExamScores()->count())->toBe(0);
});

test('staff enter the exam scores of a CM2 class', function () {
    $this->actingAs(User::factory()->create());
    giveAdmissionScores($this->awaEnrollment, [12, 9, 10, null]);

    Livewire::test(AdmissionPage::class)
        ->assertSet('selection.school_class_id', $this->schoolClass->id)
        ->assertCanSeeTableRecords([$this->awaEnrollment, $this->yaoEnrollment])
        ->call('updateTableColumnState', AdmissionExam::MockExam2->value, (string) $this->awaEnrollment->getKey(), '11')
        ->assertSee('10,50')
        ->assertSee('Admis(e)')
        ->call('updateTableColumnState', AdmissionExam::MockExam2->value, (string) $this->yaoEnrollment->getKey(), '25');

    expect($this->awaEnrollment->fresh()->admissionExamScore(AdmissionExam::MockExam2))->toBe(11.0)
        ->and($this->yaoEnrollment->admissionExamScores()->count())->toBe(0);
});

test('teachers cannot open the admission page', function () {
    $this->actingAs(User::factory()->teacher()->create());

    expect(AdmissionPage::canAccess())->toBeFalse();
});

test('the CM2 report card of the third term shows the MAP and the decision', function () {
    giveAdmissionScores($this->awaEnrollment, [12, 9, 10, 11]);
    $thirdTerm = $this->schoolClass->schoolYear->terms->last();
    $results = new TermResults($this->schoolClass, $thirdTerm);

    $html = view('grades.report-card', [
        'results' => $results,
        'annual' => new AnnualResults($this->schoolClass),
        'attendance' => collect(),
        'rows' => $results->students->only([$this->awa->id]),
        'schoolClass' => $this->schoolClass,
        'term' => $thirdTerm,
    ])->render();

    expect($html)
        ->toContain("Moyenne d'admission en 6e (MAP)")
        ->toContain('10,50 / 20')
        ->toContain('Admis(e) en 6e');
});
