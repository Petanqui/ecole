<?php

use App\Enums\Core\Gender;
use App\Enums\Core\GuardianRelationship;
use App\Filament\Gestion\Resources\Core\Families\Pages\CreateFamily;
use App\Filament\Gestion\Resources\Core\Families\Pages\EditFamily;
use App\Filament\Gestion\Resources\Core\Families\Pages\ListFamilies;
use App\Filament\Gestion\Resources\Core\Families\RelationManagers\GuardiansRelationManager;
use App\Filament\Gestion\Resources\Core\Families\RelationManagers\StudentsRelationManager;
use App\Models\Core\Family;
use App\Models\Core\Guardian;
use App\Models\Core\Student;
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

test('staff can create a family', function () {
    Livewire::test(CreateFamily::class)
        ->fillForm(['name' => 'Famille Koné', 'address' => 'Cocody, Angré'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Family::sole()->name)->toBe('Famille Koné');
});

test('staff can add a parent to a family with a local phone number', function () {
    $family = Family::factory()->create();

    Livewire::test(GuardiansRelationManager::class, ['ownerRecord' => $family, 'pageClass' => EditFamily::class])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'relationship' => GuardianRelationship::Mother->value,
            'last_name' => 'Koné',
            'first_name' => 'Mariam',
            'phone' => '07 12 34 56 78',
        ])
        ->assertHasNoFormErrors();

    expect($family->guardians()->sole())
        ->phone->toBe('+2250712345678')
        ->relationship->toBe(GuardianRelationship::Mother);
});

test('an invalid parent phone number is rejected', function () {
    $family = Family::factory()->create();

    Livewire::test(GuardiansRelationManager::class, ['ownerRecord' => $family, 'pageClass' => EditFamily::class])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'relationship' => GuardianRelationship::Father->value,
            'last_name' => 'Koné',
            'first_name' => 'Ibrahim',
            'phone' => '07 12',
        ])
        ->assertHasFormErrors(['phone']);

    expect($family->guardians()->count())->toBe(0);
});

test('staff can add a child from the family page', function () {
    $family = Family::factory()->create();

    Livewire::test(StudentsRelationManager::class, ['ownerRecord' => $family, 'pageClass' => EditFamily::class])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'last_name' => 'Koné',
            'first_name' => 'Awa',
            'gender' => Gender::Female->value,
            'birth_date' => '2016-03-12',
        ])
        ->assertHasNoFormErrors();

    expect($family->students()->sole()->first_name)->toBe('Awa');
});

test('families can be found by a parent phone number', function () {
    $family = Guardian::factory()->create(['phone' => '+2250712345678'])->family;
    $otherFamily = Guardian::factory()->create(['phone' => '+2250199999999'])->family;

    Livewire::test(ListFamilies::class)
        ->searchTable('0712345678')
        ->assertCanSeeTableRecords([$family])
        ->assertCanNotSeeTableRecords([$otherFamily]);
});

test('a family with children cannot be deleted', function () {
    $familyWithChildren = Student::factory()->create()->family;
    $emptyFamily = Family::factory()->create();

    Livewire::test(EditFamily::class, ['record' => $familyWithChildren->getRouteKey()])
        ->assertActionHidden(DeleteAction::class);

    Livewire::test(EditFamily::class, ['record' => $emptyFamily->getRouteKey()])
        ->assertActionVisible(DeleteAction::class);
});
