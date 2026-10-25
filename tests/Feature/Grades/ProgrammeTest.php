<?php

use App\Enums\Core\GradeLevel;
use App\Filament\Gestion\Resources\Core\SchoolClasses\Pages\EditSchoolClass;
use App\Filament\Gestion\Resources\Core\SchoolClasses\RelationManagers\ClassSubjectsRelationManager;
use App\Filament\Gestion\Resources\Core\SchoolClasses\SchoolClassResource;
use App\Filament\Gestion\Resources\Core\Students\StudentResource;
use App\Filament\Gestion\Resources\Grades\Subjects\SubjectResource;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\Grades\Subject;
use App\Models\User;
use Database\Seeders\SubjectSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SubjectSeeder::class);
});

/**
 * @return array<string, int>
 */
function programmeOf(SchoolClass $schoolClass): array
{
    return $schoolClass->classSubjects()->with('subject')->get()
        ->mapWithKeys(fn ($classSubject) => [$classSubject->subject->code => $classSubject->coefficient])
        ->sortKeys()
        ->all();
}

test('a new school year gets its three terms', function () {
    $schoolYear = SchoolYear::factory()->create();

    expect($schoolYear->terms->pluck('name')->all())->toBe(['1er trimestre', '2e trimestre', '3e trimestre']);
});

test('a new class receives the default programme of its level', function () {
    $schoolClass = SchoolClass::factory()->create(['level' => GradeLevel::Sixieme, 'section' => '1']);

    expect(programmeOf($schoolClass))->toBe(collect(config('grades.programmes.6e'))->sortKeys()->all());
});

test('upper secondary classes receive the programme of their series', function () {
    $terminaleD = SchoolClass::factory()->create(['level' => GradeLevel::Terminale, 'section' => 'D2']);
    $terminaleC = SchoolClass::factory()->create(['level' => GradeLevel::Terminale, 'section' => 'C']);

    expect(programmeOf($terminaleD)['SVT'])->toBe(4)
        ->and(programmeOf($terminaleC)['MATH'])->toBe(5)
        ->and(programmeOf($terminaleC)['SVT'])->toBe(2);
});

test('a class whose series is unknown starts with an empty programme', function () {
    $schoolClass = SchoolClass::factory()->create(['level' => GradeLevel::Seconde, 'section' => '1']);

    expect($schoolClass->classSubjects()->count())->toBe(0);
});

test('primary classes have every subject with coefficient 1', function () {
    $schoolClass = SchoolClass::factory()->create(['level' => GradeLevel::Cm2, 'section' => 'A']);

    expect(programmeOf($schoolClass))->toHaveCount(count(config('grades.programmes.primary')))
        ->and(array_unique(array_values(programmeOf($schoolClass))))->toBe([1]);
});

test('completing with the default programme keeps edited coefficients and teachers', function () {
    $this->actingAs(User::factory()->create());
    $teacher = User::factory()->teacher()->create();
    $schoolClass = SchoolClass::factory()->create(['level' => GradeLevel::Sixieme, 'section' => '1']);

    $maths = $schoolClass->classSubjects()->whereRelation('subject', 'code', 'MATH')->sole();
    $maths->update(['coefficient' => 5, 'teacher_id' => $teacher->id]);
    $schoolClass->classSubjects()->whereRelation('subject', 'code', 'FR')->delete();

    Livewire::test(ClassSubjectsRelationManager::class, ['ownerRecord' => $schoolClass, 'pageClass' => EditSchoolClass::class])
        ->callAction(TestAction::make('applyDefaultProgramme')->table())
        ->assertNotified('1 matière(s) ajoutée(s)');

    expect($maths->fresh())
        ->coefficient->toBe(5)
        ->teacher_id->toBe($teacher->id)
        ->and(programmeOf($schoolClass)['FR'])->toBe(4);
});

test('a subject cannot be added twice to the same class', function () {
    $this->actingAs(User::factory()->create());
    $schoolClass = SchoolClass::factory()->create(['level' => GradeLevel::Sixieme, 'section' => '1']);

    Livewire::test(ClassSubjectsRelationManager::class, ['ownerRecord' => $schoolClass, 'pageClass' => EditSchoolClass::class])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'subject_id' => Subject::where('code', 'MATH')->value('id'),
            'coefficient' => 2,
        ])
        ->assertHasFormErrors(['subject_id' => 'unique']);
});

test('teachers cannot open the school management screens', function () {
    $this->actingAs(User::factory()->teacher()->create());

    $this->get('/gestion')->assertOk();

    foreach ([SchoolClassResource::class, StudentResource::class, SubjectResource::class] as $resource) {
        expect($resource::canAccess())->toBeFalse();
        $this->get($resource::getUrl())->assertForbidden();
    }
});
