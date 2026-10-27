<?php

use App\Enums\Core\GradeLevel;
use App\Filament\Gestion\Resources\Messaging\Conversations\ConversationResource;
use App\Filament\Gestion\Resources\Messaging\Conversations\Pages\ManageConversations;
use App\Filament\Gestion\Resources\Messaging\Conversations\Pages\ViewConversation;
use App\Models\Core\Enrollment;
use App\Models\Core\Guardian;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\Grades\ClassSubject;
use App\Models\Messaging\Conversation;
use App\Models\User;
use Filament\Actions\CreateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $schoolYear = SchoolYear::factory()->current()->create();
    $this->sixieme = SchoolClass::factory()->for($schoolYear)->create(['level' => GradeLevel::Sixieme, 'section' => '1']);
    $this->cm2 = SchoolClass::factory()->for($schoolYear)->create(['level' => GradeLevel::Cm2, 'section' => 'A']);

    $this->awa = Enrollment::factory()->for($this->sixieme)->create()->student;
    $this->fatou = Enrollment::factory()->for($this->cm2)->create()->student;
    $this->parent = User::factory()->guardian()->create();
    Guardian::factory()->for($this->awa->family)->for($this->parent)->create();

    $this->teacher = User::factory()->teacher()->create();
    ClassSubject::factory()->for($this->sixieme)->create(['teacher_id' => $this->teacher->id]);
});

function writeToFamily(array $data)
{
    return Livewire::test(ManageConversations::class)
        ->callAction(CreateAction::class, data: ['subject' => 'Tenue', 'body' => 'Merci de prévoir la tenue de sport.', ...$data]);
}

test('staff write to the family of a student on behalf of the administration', function () {
    $this->actingAs($staff = User::factory()->create());

    writeToFamily(['student_id' => $this->awa->id])->assertHasNoActionErrors();

    $conversation = Conversation::sole();
    expect($conversation)
        ->family_id->toBe($this->awa->family_id)
        ->student_id->toBe($this->awa->id)
        ->teacher_id->toBeNull()
        ->interlocutor->toBe('Administration')
        ->and($conversation->messages()->sole())->author_id->toBe($staff->id)
        ->and(Conversation::unreadBy($staff)->exists())->toBeFalse()
        ->and(Conversation::unreadBy($this->parent)->exists())->toBeTrue();
});

test('a teacher only writes to the families of the classes they teach, in their own name', function () {
    $this->actingAs($this->teacher);

    writeToFamily(['student_id' => $this->fatou->id])->assertHasActionErrors(['student_id']);
    writeToFamily(['student_id' => $this->awa->id])->assertHasNoActionErrors();

    expect(Conversation::sole()->teacher_id)->toBe($this->teacher->id);
});

test('a teacher only sees the conversations addressed to them', function () {
    $own = Conversation::factory()->create(['teacher_id' => $this->teacher->id]);
    $administration = Conversation::factory()->create();
    $this->actingAs($this->teacher);

    Livewire::test(ManageConversations::class)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$administration]);

    $this->get(ConversationResource::getUrl('view', ['record' => $administration]))->assertNotFound();
});

test('a message from a parent is unread until opened, then the school replies', function () {
    $conversation = Conversation::factory()->for($this->awa->family)->create(['subject' => 'Absence de vendredi']);
    $conversation->send($this->parent, 'Awa était malade vendredi.');
    $this->actingAs($staff = User::factory()->create());

    expect(ConversationResource::getNavigationBadge())->toBe('1');

    Livewire::test(ViewConversation::class, ['record' => $conversation->getKey()])
        ->assertSee('Awa était malade vendredi.')
        ->assertSee("{$this->parent->name} (parent)")
        ->fillForm(['body' => 'Merci, apportez le certificat au secrétariat.'])
        ->call('send')
        ->assertHasNoFormErrors();

    expect(ConversationResource::getNavigationBadge())->toBeNull()
        ->and($conversation->messages()->latest('id')->first())
        ->body->toBe('Merci, apportez le certificat au secrétariat.')
        ->author_id->toBe($staff->id)
        ->and(Conversation::unreadBy($this->parent)->exists())->toBeTrue();
});

test('every staff member has their own unread messages', function () {
    $conversation = Conversation::factory()->for($this->awa->family)->create();
    $conversation->send($this->parent, 'Bonjour');
    [$secretary, $principal] = User::factory()->count(2)->create();

    $conversation->markAsReadBy($secretary);

    expect(Conversation::unreadBy($secretary)->exists())->toBeFalse()
        ->and(Conversation::unreadBy($principal)->exists())->toBeTrue();
});
