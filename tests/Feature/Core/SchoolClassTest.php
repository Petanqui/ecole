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
            'name' => 'CP1 A',
            'capacity' => 45,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(SchoolClass::sole())
        ->name->toBe('CP1 A')
        ->level->toBe(GradeLevel::Cp1)
        ->school_year_id->toBe($schoolYear->id);
});

test('staff can create a secondary class', function () {
    $schoolYear = SchoolYear::factory()->current()->create();

    Livewire::test(CreateSchoolClass::class)
        ->fillForm([
            'level' => GradeLevel::Terminale->value,
            'name' => 'Tle D',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(SchoolClass::sole()->level)->toBe(GradeLevel::Terminale);
});

test('two classes of the same school year cannot share a name', function () {
    $schoolYear = SchoolYear::factory()->create();
    SchoolClass::factory()->for($schoolYear)->create(['name' => 'CP1 A']);

    Livewire::test(CreateSchoolClass::class)
        ->fillForm([
            'school_year_id' => $schoolYear->id,
            'level' => GradeLevel::Cp1->value,
            'name' => 'CP1 A',
        ])
        ->call('create')
        ->assertHasFormErrors(['name' => 'unique']);
});

test('the same class name can be reused in another school year', function () {
    SchoolClass::factory()->create(['name' => 'CP1 A']);
    $otherSchoolYear = SchoolYear::factory()->create();

    Livewire::test(CreateSchoolClass::class)
        ->fillForm([
            'school_year_id' => $otherSchoolYear->id,
            'level' => GradeLevel::Cp1->value,
            'name' => 'CP1 A',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(SchoolClass::where('name', 'CP1 A')->count())->toBe(2);
});

test('the class list shows only the current school year by default', function () {
    $currentSchoolYear = SchoolYear::factory()->current()->create();
    $currentClass = SchoolClass::factory()->for($currentSchoolYear)->create();
    $oldClass = SchoolClass::factory()->create();

    Livewire::test(ListSchoolClasses::class)
        ->assertCanSeeTableRecords([$currentClass])
        ->assertCanNotSeeTableRecords([$oldClass]);
});
