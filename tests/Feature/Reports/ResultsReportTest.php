<?php

use App\Enums\Core\GradeLevel;
use App\Filament\Gestion\Pages\Grades\TermResultsPage;
use App\Filament\Gestion\Pages\Reports\ResultsReportPage;
use App\Models\Core\Enrollment;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\Core\Student;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $schoolYear = SchoolYear::factory()->current()->create();
    $this->firstTerm = $schoolYear->terms->first();

    $this->sixieme = SchoolClass::factory()->for($schoolYear)->create(['level' => GradeLevel::Sixieme, 'section' => '1']);
    [$awa, $yao, $fatou] = Student::factory()->count(3)->sequence(
        ['last_name' => 'Koné', 'first_name' => 'Awa'],
        ['last_name' => 'Yao', 'first_name' => 'Kouassi'],
        ['last_name' => 'Diallo', 'first_name' => 'Fatou'],
    )->create()->each(fn (Student $student) => Enrollment::factory()->for($student)->for($this->sixieme)->create())->all();
    giveMarks(addSubject($this->sixieme, coefficient: 1), $this->firstTerm, 20, [$awa->id => 15, $yao->id => 8, $fatou->id => 12]);

    $this->cm1 = SchoolClass::factory()->for($schoolYear)->create(['level' => GradeLevel::Cm1, 'section' => 'A']);
    [$kouame, $mariam] = Student::factory()->count(2)->create()
        ->each(fn (Student $student) => Enrollment::factory()->for($student)->for($this->cm1)->create())->all();
    giveMarks(addSubject($this->cm1, coefficient: 1), $this->firstTerm, 10, [$kouame->id => 4, $mariam->id => 6]);
});

test('the report gives the results of every class for the term', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ResultsReportPage::class)
        ->assertSchemaStateSet(['term_id' => $this->firstTerm->id])
        ->assertTableColumnStateSet('average', '11,67 / 20', $this->sixieme->id)
        ->assertTableColumnStateSet('highest', '15,00 / 20', $this->sixieme->id)
        ->assertTableColumnStateSet('lowest', '8,00 / 20', $this->sixieme->id)
        ->assertTableColumnFormattedStateSet('pass_rate', '66,7 % (2/3)', $this->sixieme->id)
        ->assertTableColumnStateSet('top_student', 'Koné Awa', $this->sixieme->id)
        ->assertTableColumnFormattedStateSet('pass_rate', '50,0 % (1/2)', $this->cm1->id)
        ->assertTableColumnStateSet('average', '5,00 / 10', $this->cm1->id);
});

test('the report is exported as CSV and leads to the results of a class', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ResultsReportPage::class)
        ->callAction('export')
        ->assertFileDownloaded('bilan-resultats-trimestre-1.csv')
        ->assertActionHasUrl(
            TestAction::make('details')->table($this->cm1->id),
            TermResultsPage::getUrl(['classe' => $this->cm1->id, 'trimestre' => $this->firstTerm->id]),
        );

    Livewire::withQueryParams(['classe' => $this->cm1->id, 'trimestre' => $this->firstTerm->id])
        ->test(TermResultsPage::class)
        ->assertSchemaStateSet(['school_class_id' => $this->cm1->id, 'term_id' => $this->firstTerm->id]);
});

test('teachers cannot see the report', function () {
    $this->actingAs(User::factory()->teacher()->create());

    $this->get(ResultsReportPage::getUrl())->assertForbidden();
});
