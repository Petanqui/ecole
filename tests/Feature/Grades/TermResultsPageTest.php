<?php

use App\Enums\Core\GradeLevel;
use App\Filament\Gestion\Pages\Grades\TermResultsPage;
use App\Models\Core\Enrollment;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\Core\Student;
use App\Models\Grades\Assessment;
use App\Models\User;
use App\Services\Grades\TermResults;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SubjectSeeder::class);

    $this->schoolYear = SchoolYear::factory()->current()->create();
    $this->firstTerm = $this->schoolYear->terms->first();
    $this->sixieme = SchoolClass::factory()->for($this->schoolYear)->create(['level' => GradeLevel::Sixieme, 'section' => '1']);
    $this->cinquieme = SchoolClass::factory()->for($this->schoolYear)->create(['level' => GradeLevel::Cinquieme, 'section' => '1']);

    $this->awa = Student::factory()->create(['last_name' => 'Koné', 'first_name' => 'Awa']);
    $this->yao = Student::factory()->create(['last_name' => 'Yao', 'first_name' => 'Kouassi']);
    Enrollment::factory()->for($this->awa)->for($this->sixieme)->create();
    Enrollment::factory()->for($this->yao)->for($this->sixieme)->create();

    $this->maths = $this->sixieme->classSubjects()->whereRelation('subject', 'code', 'MATH')->sole();
    $assessment = Assessment::factory()->for($this->maths)->for($this->firstTerm)->create(['max_score' => 20]);
    $assessment->marks()->where('student_id', $this->awa->id)->update(['score' => 15.5]);
    $assessment->marks()->where('student_id', $this->yao->id)->update(['score' => 9]);
});

test('staff see the averages and ranks of a class', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(TermResultsPage::class)
        ->set('selection.school_class_id', $this->sixieme->id)
        ->set('selection.term_id', $this->firstTerm->id)
        ->assertCanSeeTableRecords([$this->awa->id, $this->yao->id], inOrder: true)
        ->assertSee('15,50')
        ->assertSee('1er')
        ->assertSee('2e');
});

test('the page opens on the first open term of the current year', function () {
    $this->firstTerm->update(['is_closed' => true]);
    $this->actingAs(User::factory()->create());

    Livewire::test(TermResultsPage::class)
        ->assertSet('selection.term_id', $this->schoolYear->terms[1]->id);
});

test('a teacher only reaches the classes they teach in', function () {
    $teacher = User::factory()->teacher()->create();
    $this->maths->update(['teacher_id' => $teacher->id]);
    Enrollment::factory()->for(Student::factory()->state(['last_name' => 'Diabaté']))->for($this->cinquieme)->create();
    $this->actingAs($teacher);

    Livewire::test(TermResultsPage::class)
        ->assertSet('selection.school_class_id', $this->sixieme->id)
        ->assertSee('Koné')
        ->set('selection.school_class_id', $this->cinquieme->id)
        ->assertDontSee('Diabaté')
        ->assertDontSee('Koné');
});

test('a teacher without classes and parents cannot open the page', function () {
    $this->actingAs(User::factory()->guardian()->create());
    expect(TermResultsPage::canAccess())->toBeFalse();

    $this->actingAs(User::factory()->teacher()->create());
    $this->get(TermResultsPage::getUrl())->assertOk();
});

test('staff close and reopen a term', function () {
    $this->actingAs(User::factory()->create());

    $component = Livewire::test(TermResultsPage::class)
        ->set('selection.term_id', $this->firstTerm->id)
        ->callAction('toggleTermClosure');

    expect($this->firstTerm->fresh()->is_closed)->toBeTrue();

    $component->callAction('toggleTermClosure');

    expect($this->firstTerm->fresh()->is_closed)->toBeFalse();
});

test('teachers cannot close a term', function () {
    $teacher = User::factory()->teacher()->create();
    $this->maths->update(['teacher_id' => $teacher->id]);
    $this->actingAs($teacher);

    Livewire::test(TermResultsPage::class)
        ->assertActionHidden('toggleTermClosure');
});

test('ranks are written the French way', function () {
    expect(TermResults::formatRank(1))->toBe('1er')
        ->and(TermResults::formatRank(2, isTied: true))->toBe('2e ex æquo')
        ->and(TermResults::formatAverage(13.5))->toBe('13,50');
});
