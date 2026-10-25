<?php

use App\Filament\Gestion\Resources\Core\SchoolYears\Pages\ManageSchoolYears;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('marking a school year as current unmarks the previous one', function () {
    $previous = SchoolYear::factory()->current()->create();
    $next = SchoolYear::factory()->create();

    $next->update(['is_current' => true]);

    expect($previous->fresh()->is_current)->toBeFalse()
        ->and($next->fresh()->is_current)->toBeTrue()
        ->and(SchoolYear::current()->count())->toBe(1);
});

test('creating a current school year unmarks the previous one', function () {
    $previous = SchoolYear::factory()->current()->create();

    SchoolYear::factory()->current()->create();

    expect($previous->fresh()->is_current)->toBeFalse();
});

test('staff can create a school year from the gestion panel', function () {
    Livewire::test(ManageSchoolYears::class)
        ->callAction(CreateAction::class, data: [
            'name' => '2026-2027',
            'starts_on' => '2026-09-14',
            'ends_on' => '2027-07-16',
            'is_current' => true,
        ])
        ->assertHasNoFormErrors();

    expect(SchoolYear::sole())
        ->name->toBe('2026-2027')
        ->is_current->toBeTrue();
});

test('the end date must be after the start date', function () {
    Livewire::test(ManageSchoolYears::class)
        ->callAction(CreateAction::class, data: [
            'name' => '2026-2027',
            'starts_on' => '2026-09-14',
            'ends_on' => '2026-09-01',
        ])
        ->assertHasFormErrors(['ends_on' => 'after']);

    expect(SchoolYear::count())->toBe(0);
});

test('a school year name must be unique', function () {
    SchoolYear::factory()->create(['name' => '2026-2027']);

    Livewire::test(ManageSchoolYears::class)
        ->callAction(CreateAction::class, data: [
            'name' => '2026-2027',
            'starts_on' => '2026-09-14',
            'ends_on' => '2027-07-16',
        ])
        ->assertHasFormErrors(['name' => 'unique']);
});

test('a school year with classes cannot be deleted', function () {
    $schoolYear = SchoolYear::factory()->has(SchoolClass::factory(), 'classes')->create();
    $emptySchoolYear = SchoolYear::factory()->create();

    Livewire::test(ManageSchoolYears::class)
        ->assertActionHidden(TestAction::make(DeleteAction::class)->table($schoolYear))
        ->assertActionVisible(TestAction::make(DeleteAction::class)->table($emptySchoolYear));
});
