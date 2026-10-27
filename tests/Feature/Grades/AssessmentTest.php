<?php

use App\Enums\Core\GradeLevel;
use App\Enums\Grades\AssessmentType;
use App\Filament\Gestion\Resources\Grades\Assessments\AssessmentResource;
use App\Filament\Gestion\Resources\Grades\Assessments\Pages\CreateAssessment;
use App\Filament\Gestion\Resources\Grades\Assessments\Pages\EditAssessment;
use App\Filament\Gestion\Resources\Grades\Assessments\Pages\ListAssessments;
use App\Filament\Gestion\Resources\Grades\Assessments\RelationManagers\MarksRelationManager;
use App\Models\Core\Enrollment;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\Grades\Assessment;
use App\Models\Grades\ClassSubject;
use App\Models\User;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SubjectSeeder::class);

    $this->teacher = User::factory()->teacher()->create();
    $this->schoolClass = SchoolClass::factory()
        ->for(SchoolYear::factory()->current())
        ->create(['level' => GradeLevel::Sixieme, 'section' => '1']);
    $this->maths = $this->schoolClass->classSubjects()->whereRelation('subject', 'code', 'MATH')->sole();
    $this->maths->update(['teacher_id' => $this->teacher->id]);

    Enrollment::factory()->count(3)->for($this->schoolClass)->create();
});

function makeAssessment(ClassSubject $classSubject, array $attributes = []): Assessment
{
    return Assessment::factory()->for($classSubject)->create([
        'term_id' => $classSubject->schoolClass->schoolYear->terms->first()->id,
        ...$attributes,
    ]);
}

test('a new assessment has an empty mark for every enrolled student', function () {
    $assessment = makeAssessment($this->maths);

    expect($assessment->marks)->toHaveCount(3)
        ->each(fn ($mark) => $mark->score->toBeNull());
});

test('students enrolled later are added to the assessment', function () {
    $assessment = makeAssessment($this->maths);
    Enrollment::factory()->for($this->schoolClass)->create();

    expect($assessment->syncStudents())->toBe(1)
        ->and($assessment->marks()->count())->toBe(4);
});

test('an absent student has no score', function () {
    $mark = makeAssessment($this->maths)->marks()->first();
    $mark->update(['score' => 12]);

    $mark->update(['is_absent' => true]);

    expect($mark->fresh()->score)->toBeNull();
});

test('a teacher creates an assessment and lands on grade entry', function () {
    $this->actingAs($this->teacher);

    $component = Livewire::test(CreateAssessment::class)
        ->fillForm(['class_subject_id' => $this->maths->id])
        ->assertSchemaStateSet([
            'term_id' => $this->schoolClass->schoolYear->terms->first()->id,
            'max_score' => 20,
        ])
        ->fillForm(['type' => AssessmentType::Test->value, 'max_score' => 10, 'held_on' => '2026-10-20'])
        ->call('create')
        ->assertHasNoFormErrors();

    $assessment = Assessment::sole();
    $component->assertRedirect(AssessmentResource::getUrl('edit', ['record' => $assessment]));
    expect($assessment)->max_score->toBe(10)->type->toBe(AssessmentType::Test);
});

test('primary compositions are out of 50 by default', function () {
    $this->actingAs(User::factory()->create());
    $cm2 = SchoolClass::factory()->for($this->schoolClass->schoolYear)->create(['level' => GradeLevel::Cm2, 'section' => 'A']);

    Livewire::test(CreateAssessment::class)
        ->fillForm(['class_subject_id' => $cm2->classSubjects()->first()->id])
        ->fillForm(['type' => AssessmentType::Composition->value])
        ->assertSchemaStateSet(['max_score' => 50]);
});

test('a teacher cannot create an assessment for a subject they do not teach', function () {
    $this->actingAs($this->teacher);
    $french = $this->schoolClass->classSubjects()->whereRelation('subject', 'code', 'FR-OG')->sole();

    Livewire::test(CreateAssessment::class)
        ->fillForm(['class_subject_id' => $french->id, 'term_id' => $this->schoolClass->schoolYear->terms->first()->id])
        ->call('create')
        ->assertHasFormErrors(['class_subject_id']);

    expect(Assessment::count())->toBe(0);
});

test('a teacher only sees the assessments of their own subjects', function () {
    $own = makeAssessment($this->maths);
    $other = makeAssessment($this->schoolClass->classSubjects()->whereRelation('subject', 'code', 'ANG')->sole());

    $this->actingAs($this->teacher);

    Livewire::test(ListAssessments::class)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$other]);

    $this->get(AssessmentResource::getUrl('edit', ['record' => $other]))->assertNotFound();
});

test('a teacher can no longer change marks once the term is closed', function () {
    $assessment = makeAssessment($this->maths);
    $assessment->term->update(['is_closed' => true]);

    $this->actingAs($this->teacher)
        ->get(AssessmentResource::getUrl('edit', ['record' => $assessment]))
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->get(AssessmentResource::getUrl('edit', ['record' => $assessment]))
        ->assertOk();
});

test('marks are typed into the grade entry table and cannot exceed the maximum score', function () {
    $this->actingAs($this->teacher);
    $assessment = makeAssessment($this->maths, ['max_score' => 20]);
    $mark = $assessment->marks()->first();

    $component = Livewire::test(MarksRelationManager::class, ['ownerRecord' => $assessment, 'pageClass' => EditAssessment::class]);

    $component->call('updateTableColumnState', 'score', (string) $mark->getKey(), '14.5');
    expect($mark->fresh()->score)->toBe(14.5);

    $component->call('updateTableColumnState', 'score', (string) $mark->getKey(), '25');
    expect($mark->fresh()->score)->toBe(14.5);
});

test('the list shows how many marks are entered', function () {
    $this->actingAs(User::factory()->create());
    $assessment = makeAssessment($this->maths);
    $assessment->marks()->first()->update(['score' => 12]);
    $assessment->marks()->skip(1)->first()->update(['is_absent' => true]);

    Livewire::test(ListAssessments::class)
        ->assertTableColumnFormattedStateSet('entered_marks_count', '2 / 3', $assessment);
});
