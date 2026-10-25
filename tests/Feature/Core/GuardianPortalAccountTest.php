<?php

use App\Enums\Core\UserRole;
use App\Exceptions\Core\PhoneNumberAlreadyUsedException;
use App\Filament\Auth\Login;
use App\Filament\Gestion\Resources\Core\Families\Pages\EditFamily;
use App\Filament\Gestion\Resources\Core\Families\RelationManagers\GuardiansRelationManager;
use App\Models\Core\Guardian;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('creating a portal account gives the parent a guardian login with their phone number', function () {
    $guardian = Guardian::factory()->create(['phone' => '+2250712345678']);

    $password = $guardian->createPortalAccount();

    expect($guardian->fresh()->user)
        ->phone->toBe('+2250712345678')
        ->role->toBe(UserRole::Guardian)
        ->name->toBe($guardian->full_name)
        ->and(Hash::check($password, $guardian->fresh()->user->password))->toBeTrue()
        ->and($password)->toHaveLength(8)->not->toMatch('/[0OoIl1]/');
});

test('the parent can sign in to the portal with the generated password', function () {
    $guardian = Guardian::factory()->create(['phone' => '+2250712345678']);
    $password = $guardian->createPortalAccount();

    Filament::setCurrentPanel('portail');

    Livewire::test(Login::class)
        ->fillForm(['phone' => '07 12 34 56 78', 'password' => $password])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($guardian->fresh()->user);
});

test('a portal account cannot be created when the phone number already has an account', function () {
    User::factory()->create(['phone' => '+2250712345678']);
    $guardian = Guardian::factory()->create(['phone' => '+2250712345678']);

    expect(fn () => $guardian->createPortalAccount())->toThrow(PhoneNumberAlreadyUsedException::class);
    expect($guardian->fresh()->user_id)->toBeNull();
});

test('resetting the password invalidates the old one', function () {
    $guardian = Guardian::factory()->create();
    $oldPassword = $guardian->createPortalAccount();

    $newPassword = $guardian->fresh()->resetPortalPassword();

    $user = $guardian->fresh()->user;
    expect(Hash::check($newPassword, $user->password))->toBeTrue()
        ->and(Hash::check($oldPassword, $user->password))->toBeFalse();
});

test('deleting a parent also deletes their portal account', function () {
    $guardian = Guardian::factory()->create();
    $guardian->createPortalAccount();
    $userId = $guardian->fresh()->user_id;

    $guardian->delete();

    expect(User::find($userId))->toBeNull();
});

test('changing the parent phone number updates their login', function () {
    $guardian = Guardian::factory()->create();
    $guardian->createPortalAccount();

    $guardian->fresh()->update(['phone' => '05 99 88 77 66']);

    expect($guardian->fresh()->user->phone)->toBe('+2250599887766');
});

test('staff can create a portal account from the family page', function () {
    $this->actingAs(User::factory()->create());
    $guardian = Guardian::factory()->create();

    Livewire::test(GuardiansRelationManager::class, ['ownerRecord' => $guardian->family, 'pageClass' => EditFamily::class])
        ->callAction(TestAction::make('createPortalAccount')->table($guardian))
        ->assertNotified('Accès portail créé');

    expect($guardian->fresh()->user)->not->toBeNull();
});

test('the family page warns when the phone number already has an account', function () {
    $this->actingAs(User::factory()->create());
    User::factory()->create(['phone' => '+2250712345678']);
    $guardian = Guardian::factory()->create(['phone' => '+2250712345678']);

    Livewire::test(GuardiansRelationManager::class, ['ownerRecord' => $guardian->family, 'pageClass' => EditFamily::class])
        ->callAction(TestAction::make('createPortalAccount')->table($guardian))
        ->assertNotified('Un compte utilise déjà le numéro +2250712345678.');

    expect($guardian->fresh()->user_id)->toBeNull();
});

test('a parent with an account cannot take a phone number used by another account', function () {
    $this->actingAs(User::factory()->create());
    User::factory()->create(['phone' => '+2250599887766']);
    $guardian = Guardian::factory()->create();
    $guardian->createPortalAccount();

    Livewire::test(GuardiansRelationManager::class, ['ownerRecord' => $guardian->family, 'pageClass' => EditFamily::class])
        ->callAction(TestAction::make(EditAction::class)->table($guardian->fresh()), data: ['phone' => '05 99 88 77 66'])
        ->assertHasFormErrors(['phone']);
});
