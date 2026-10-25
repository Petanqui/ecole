<?php

use App\Enums\Core\GradeLevel;
use App\Filament\Gestion\Resources\Core\SchoolClasses\Pages\CreateSchoolClass;
use App\Filament\Gestion\Resources\Core\SchoolClasses\Pages\ListSchoolClasses;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('staff can create a class from the gestion panel', function () {
    $schoolYear = SchoolYear::factory()->current()->create();

    Livewire::test(CreateSchoolClass::class)
        ->assertSchemaStateSet(['school_year_id' => $schoolYear->id])
        ->fillForm([
            'level' => GradeLevel::Cp1->value,
            'section' => 'A',
            'capacity' => 45,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(SchoolClass::sole())
        ->name->toBe('CP1 A')
        ->level->toBe(GradeLevel::Cp1)
        ->school_year_id->toBe($schoolYear->id);
});

test('the class name is built from the level and the section', function (GradeLevel $level, string $section, string $expectedName) {
    $schoolClass = SchoolClass::factory()->create(['level' => $level, 'section' => $section]);

    expect($schoolClass->name)->toBe($expectedName);
})->with([
    [GradeLevel::Sixieme, '1', '6e1'],
    [GradeLevel::Quatrieme, '3', '4e3'],
    [GradeLevel::Seconde, 'C1', '2nde C1'],
    [GradeLevel::Terminale, 'D', 'Terminale D'],
    [GradeLevel::Cp1, 'A', 'CP1 A'],
]);

test('the same section can be used at different levels of the same year', function () {
    $schoolYear = SchoolYear::factory()->current()->create();

    foreach ([GradeLevel::Sixieme, GradeLevel::Cinquieme, GradeLevel::Quatrieme] as $level) {
        Livewire::test(CreateSchoolClass::class)
            ->fillForm(['level' => $level->value, 'section' => '1'])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    expect($schoolYear->classes()->orderBy('id')->pluck('name')->all())->toBe(['6e1', '5e1', '4e1']);
});

test('a level cannot have the same section twice in a school year', function () {
    $schoolYear = SchoolYear::factory()->create();
    SchoolClass::factory()->for($schoolYear)->create(['level' => GradeLevel::Sixieme, 'section' => '1']);

    Livewire::test(CreateSchoolClass::class)
        ->fillForm([
            'school_year_id' => $schoolYear->id,
            'level' => GradeLevel::Sixieme->value,
            'section' => '1',
        ])
        ->call('create')
        ->assertHasFormErrors(['section' => 'unique']);
});

test('the same class can exist again in another school year', function () {
    SchoolClass::factory()->create(['level' => GradeLevel::Sixieme, 'section' => '1']);
    $otherSchoolYear = SchoolYear::factory()->create();

    Livewire::test(CreateSchoolClass::class)
        ->fillForm([
            'school_year_id' => $otherSchoolYear->id,
            'level' => GradeLevel::Sixieme->value,
            'section' => '1',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(SchoolClass::where('name', '6e1')->count())->toBe(2);
});

test('staff can create a secondary class', function () {
    SchoolYear::factory()->current()->create();

    Livewire::test(CreateSchoolClass::class)
        ->fillForm([
            'level' => GradeLevel::Terminale->value,
            'section' => 'D',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(SchoolClass::sole())
        ->level->toBe(GradeLevel::Terminale)
        ->name->toBe('Terminale D');
});

test('the class list shows only the current school year by default', function () {
    $currentSchoolYear = SchoolYear::factory()->current()->create();
    $currentClass = SchoolClass::factory()->for($currentSchoolYear)->create();
    $oldClass = SchoolClass::factory()->create();

    Livewire::test(ListSchoolClasses::class)
        ->assertCanSeeTableRecords([$currentClass])
        ->assertCanNotSeeTableRecords([$oldClass]);
});
