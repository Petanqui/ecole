<?php

use App\Filament\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('gestion');
});

test('a user can log in with a local phone number and password', function () {
    $user = User::factory()->create(['phone' => '+2250700000000']);

    Livewire::test(Login::class)
        ->fillForm(['phone' => '07 00 00 00 00', 'password' => 'password'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($user);
});

test('a wrong password is rejected', function () {
    User::factory()->create(['phone' => '+2250700000000']);

    Livewire::test(Login::class)
        ->fillForm(['phone' => '0700000000', 'password' => 'wrong-password'])
        ->call('authenticate')
        ->assertHasFormErrors(['phone']);

    $this->assertGuest();
});

test('someone with the right password at the wrong door is told where to sign in', function () {
    User::factory()->guardian()->create(['phone' => '+2250700000000']);

    $rightPassword = Livewire::test(Login::class)
        ->fillForm(['phone' => '0700000000', 'password' => 'password'])
        ->call('authenticate');

    expect($rightPassword->errors()->first('data.phone'))
        ->toBe('Ce compte est un compte parent : connectez-vous sur le portail, à l\'adresse '.Filament::getPanel('portail')->getLoginUrl());
    $this->assertGuest();

    $wrongPassword = Livewire::test(Login::class)
        ->fillForm(['phone' => '0700000000', 'password' => 'wrong-password'])
        ->call('authenticate');

    expect($wrongPassword->errors()->first('data.phone'))->toBe(__('filament-panels::auth/pages/login.messages.failed'));
});

test('a user can be created without an e-mail address', function () {
    $user = User::factory()->create(['email' => null, 'phone' => '07 11 22 33 44']);

    expect($user->fresh())
        ->email->toBeNull()
        ->phone->toBe('+2250711223344');
});

test('two users cannot share the same phone number', function () {
    User::factory()->create(['phone' => '+2250700000000']);

    User::factory()->create(['phone' => '07 00 00 00 00']);
})->throws(QueryException::class);

test('the create user command creates a user with a normalized phone number', function () {
    $this->artisan('app:create-user', [
        '--name' => 'Awa Koné',
        '--phone' => '07 12 34 56 78',
        '--password' => 'secret-password',
    ])->assertSuccessful();

    $user = User::where('phone', '+2250712345678')->sole();

    expect($user)
        ->name->toBe('Awa Koné')
        ->email->toBeNull();
});

test('the create user command refuses a phone number that is already taken', function () {
    User::factory()->create(['phone' => '+2250712345678']);

    $this->artisan('app:create-user', [
        '--name' => 'Awa Koné',
        '--phone' => '0712345678',
        '--password' => 'secret-password',
    ])->assertFailed();

    expect(User::count())->toBe(1);
});
