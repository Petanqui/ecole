<?php

use App\Enums\Core\UserRole;
use App\Filament\Gestion\Resources\Users\Pages\ManageUsers;
use App\Filament\Gestion\Resources\Users\UserResource;
use App\Filament\Portail\Widgets\MyChildren;
use App\Models\Core\Enrollment;
use App\Models\Core\Guardian;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\Core\Student;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('each role can only open its own panel, and is sent back to it from the other one', function (string $state, string $allowedPanel, string $forbiddenPanel, string $forbiddenPage) {
    $user = User::factory()->{$state}()->create();

    $this->actingAs($user)->get("/{$allowedPanel}")->assertOk();
    $this->actingAs($user)->get("/{$forbiddenPanel}")->assertRedirect("/{$allowedPanel}");
    $this->actingAs($user)->get("/{$forbiddenPanel}/{$forbiddenPage}")->assertRedirect("/{$allowedPanel}");
})->with([
    'administrator' => ['admin', 'gestion', 'portail', 'messages'],
    'parent' => ['guardian', 'portail', 'gestion', 'caisse'],
]);

test('staff can open the gestion panel but not the portal', function () {
    $staff = User::factory()->create();

    $this->actingAs($staff)->get('/gestion')->assertOk();
    $this->actingAs($staff)->get('/portail')->assertRedirect('/gestion');
});

test('panel access is enforced outside the local environment', function () {
    $this->app['env'] = 'production';
    $parent = User::factory()->guardian()->create();

    $this->actingAs($parent)->get('/gestion')->assertRedirect('/portail');
});

test('only administrators can manage staff accounts', function () {
    $this->actingAs(User::factory()->create());
    expect(UserResource::canViewAny())->toBeFalse();
    $this->get(UserResource::getUrl())->assertForbidden();

    $this->actingAs(User::factory()->admin()->create());
    expect(UserResource::canViewAny())->toBeTrue();
});

test('an administrator can create a staff account', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ManageUsers::class)
        ->callAction(CreateAction::class, data: [
            'name' => 'Aya Traoré',
            'phone' => '05 11 22 33 44',
            'role' => UserRole::Staff->value,
            'password' => 'secret-password',
        ])
        ->assertHasNoFormErrors();

    expect(User::where('phone', '+2250511223344')->sole())
        ->role->toBe(UserRole::Staff)
        ->name->toBe('Aya Traoré');
});

test('a staff account cannot reuse a phone number, whatever its format', function () {
    $this->actingAs(User::factory()->admin()->create());
    User::factory()->create(['phone' => '+2250511223344']);

    Livewire::test(ManageUsers::class)
        ->callAction(CreateAction::class, data: [
            'name' => 'Aya Traoré',
            'phone' => '05 11 22 33 44',
            'role' => UserRole::Staff->value,
            'password' => 'secret-password',
        ])
        ->assertHasFormErrors(['phone' => 'unique']);
});

test('parent accounts are not listed with staff accounts', function () {
    $this->actingAs($admin = User::factory()->admin()->create());
    $parent = User::factory()->guardian()->create();

    Livewire::test(ManageUsers::class)
        ->assertCanSeeTableRecords([$admin])
        ->assertCanNotSeeTableRecords([$parent]);
});

test('an administrator cannot delete their own account', function () {
    $this->actingAs($admin = User::factory()->admin()->create());
    $colleague = User::factory()->create();

    Livewire::test(ManageUsers::class)
        ->assertActionHidden(TestAction::make(DeleteAction::class)->table($admin))
        ->assertActionVisible(TestAction::make(DeleteAction::class)->table($colleague));
});

test('a parent only sees the children of their own family', function () {
    $schoolYear = SchoolYear::factory()->current()->create();
    $parent = User::factory()->guardian()->create();
    $guardian = Guardian::factory()->for($parent)->create();

    $child = Student::factory()->for($guardian->family)->create();
    Enrollment::factory()->for($child)->for(SchoolClass::factory()->for($schoolYear)->state(['section' => '1', 'level' => '6e']))->create();
    $otherChild = Student::factory()->create();

    Filament::setCurrentPanel('portail');
    $this->actingAs($parent);

    Livewire::test(MyChildren::class)
        ->assertCanSeeTableRecords([$child])
        ->assertCanNotSeeTableRecords([$otherChild])
        ->assertSee('6e1');
});
