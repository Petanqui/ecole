<?php

use App\Enums\Core\Gender;
use App\Filament\Gestion\Resources\Core\Students\Pages\CreateStudent;
use App\Filament\Gestion\Resources\Core\Students\Pages\ListStudents;
use App\Models\Core\Family;
use App\Models\Core\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('new students receive sequential codes for the current year', function () {
    $this->travelTo('2026-09-15');

    $first = Student::factory()->create();
    $second = Student::factory()->create();

    expect($first->code)->toBe('EL-2026-0001')
        ->and($second->code)->toBe('EL-2026-0002');
});

test('student code numbering restarts every year', function () {
    $this->travelTo('2026-12-20');
    Student::factory()->count(2)->create();

    $this->travelTo('2027-01-05');
    $student = Student::factory()->create();

    expect($student->code)->toBe('EL-2027-0001');
});

test('a deleted student code is never handed out again', function () {
    $this->travelTo('2026-09-15');
    Student::factory()->create()->delete();

    expect(Student::factory()->create()->code)->toBe('EL-2026-0002');
});

test('the MENA number is stored in upper case and blank values become null', function () {
    $withNumber = Student::factory()->create(['mena_number' => ' 12345678 a ']);
    $withoutNumber = Student::factory()->create(['mena_number' => '']);

    expect($withNumber->mena_number)->toBe('12345678A')
        ->and($withoutNumber->mena_number)->toBeNull();
});

test('staff can register a student from the gestion panel', function () {
    $this->travelTo('2026-09-15');
    $family = Family::factory()->create();

    Livewire::test(CreateStudent::class)
        ->fillForm([
            'family_id' => $family->id,
            'last_name' => 'Koné',
            'first_name' => 'Awa',
            'gender' => Gender::Female->value,
            'birth_date' => '2016-03-12',
            'birth_place' => 'Abidjan',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Student::sole())
        ->code->toBe('EL-2026-0001')
        ->full_name->toBe('Koné Awa')
        ->mena_number->toBeNull()
        ->family_id->toBe($family->id);
});

test('two students cannot share a MENA number, whatever the spacing or case', function () {
    Student::factory()->create(['mena_number' => '12345678A']);

    Livewire::test(CreateStudent::class)
        ->fillForm([
            'family_id' => Family::factory()->create()->id,
            'last_name' => 'Koné',
            'first_name' => 'Awa',
            'gender' => Gender::Female->value,
            'birth_date' => '2016-03-12',
            'mena_number' => '12345678 a',
        ])
        ->call('create')
        ->assertHasFormErrors(['mena_number' => 'unique']);
});

test('students can be found by code or MENA number', function () {
    $student = Student::factory()->create(['mena_number' => '12345678A']);
    $otherStudent = Student::factory()->create();

    Livewire::test(ListStudents::class)
        ->searchTable($student->code)
        ->assertCanSeeTableRecords([$student])
        ->assertCanNotSeeTableRecords([$otherStudent])
        ->searchTable('12345678A')
        ->assertCanSeeTableRecords([$student])
        ->assertCanNotSeeTableRecords([$otherStudent]);
});

test('a birth date in the future is rejected', function () {
    Livewire::test(CreateStudent::class)
        ->fillForm([
            'family_id' => Family::factory()->create()->id,
            'last_name' => 'Koné',
            'first_name' => 'Awa',
            'gender' => Gender::Female->value,
            'birth_date' => now()->addDay()->toDateString(),
        ])
        ->call('create')
        ->assertHasFormErrors(['birth_date']);
});
