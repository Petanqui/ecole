<?php

use App\Enums\Core\GradeLevel;
use App\Enums\Messaging\AnnouncementAudience;
use App\Filament\Gestion\Resources\Messaging\Announcements\Pages\ManageAnnouncements;
use App\Filament\Portail\Pages\Announcements;
use App\Models\Core\Enrollment;
use App\Models\Core\Guardian;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\Grades\ClassSubject;
use App\Models\Messaging\Announcement;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->schoolYear = SchoolYear::factory()->current()->create();
    $this->sixieme = SchoolClass::factory()->for($this->schoolYear)->create(['level' => GradeLevel::Sixieme, 'section' => '1']);
    $this->cm2 = SchoolClass::factory()->for($this->schoolYear)->create(['level' => GradeLevel::Cm2, 'section' => 'A']);

    $this->sixiemeParent = parentOfChildIn($this->sixieme);
    $this->cm2Parent = parentOfChildIn($this->cm2);
});

function parentOfChildIn(SchoolClass $schoolClass): User
{
    $enrollment = Enrollment::factory()->for($schoolClass)->create();
    $parent = User::factory()->guardian()->create();
    Guardian::factory()->for($enrollment->student->family)->for($parent)->create();

    return $parent;
}

test('an announcement reaches the parents of the whole school, a level or a class', function () {
    $school = Announcement::factory()->for($this->schoolYear)->create();
    $level = Announcement::factory()->for($this->schoolYear)->create(['audience' => AnnouncementAudience::Level, 'level' => GradeLevel::Cm2]);
    $class = Announcement::factory()->forClass($this->sixieme)->create();
    Announcement::factory()->for(SchoolYear::factory())->create();

    expect($school->recipients()->pluck('id')->sort()->values()->all())->toBe([$this->sixiemeParent->id, $this->cm2Parent->id])
        ->and($level->recipients()->pluck('id')->all())->toBe([$this->cm2Parent->id])
        ->and($class->recipients()->pluck('id')->all())->toBe([$this->sixiemeParent->id])
        ->and(Announcement::addressedTo($this->sixiemeParent)->pluck('id')->sort()->values()->all())->toBe([$school->id, $class->id]);
});

test('staff publish an announcement for a level of the current year', function () {
    $this->actingAs($staff = User::factory()->create());

    Livewire::test(ManageAnnouncements::class)
        ->callAction(CreateAction::class, data: [
            'audience' => AnnouncementAudience::Level->value,
            'level' => GradeLevel::Cm2->value,
            'title' => 'Réunion des parents de CM2',
            'body' => "Samedi à 9 h.\nPrésence obligatoire.",
        ])
        ->assertHasNoActionErrors();

    expect(Announcement::sole())
        ->school_year_id->toBe($this->schoolYear->id)
        ->author_id->toBe($staff->id)
        ->level->toBe(GradeLevel::Cm2)
        ->school_class_id->toBeNull();

    Livewire::test(ManageAnnouncements::class)
        ->assertTableColumnFormattedStateSet('readers_count', '0 / 1 parent(s)', Announcement::sole());
});

test('a teacher only writes to the parents of a class they teach, and sees only their announcements', function () {
    $teacher = User::factory()->teacher()->create();
    ClassSubject::factory()->for($this->sixieme)->create(['teacher_id' => $teacher->id]);
    $other = Announcement::factory()->for($this->schoolYear)->create();
    $this->actingAs($teacher);

    $publish = fn (array $data) => Livewire::test(ManageAnnouncements::class)
        ->callAction(CreateAction::class, data: ['title' => 'Sortie', 'body' => 'Jeudi.', ...$data]);

    $publish(['audience' => AnnouncementAudience::School->value])->assertHasActionErrors(['audience']);
    $publish(['audience' => AnnouncementAudience::SchoolClass->value, 'school_class_id' => $this->cm2->id])->assertHasActionErrors(['school_class_id']);
    $publish(['audience' => AnnouncementAudience::SchoolClass->value, 'school_class_id' => $this->sixieme->id])->assertHasNoActionErrors();

    $own = Announcement::where('author_id', $teacher->id)->sole();
    Livewire::test(ManageAnnouncements::class)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$other]);
});

test('a parent reads the announcements for their children, which are then no longer new', function () {
    $mine = Announcement::factory()->forClass($this->sixieme)->create(['title' => 'Sortie au zoo']);
    $notMine = Announcement::factory()->forClass($this->cm2)->create(['title' => 'Examen blanc']);

    Filament::setCurrentPanel('portail');
    $this->actingAs($this->sixiemeParent);

    expect(Announcements::getNavigationBadge())->toBe('1');

    Livewire::test(Announcements::class)
        ->assertSee('Sortie au zoo')
        ->assertSee('Nouveau')
        ->assertDontSee('Examen blanc');

    expect(Announcements::getNavigationBadge())->toBeNull()
        ->and($mine->readers()->pluck('users.id')->all())->toBe([$this->sixiemeParent->id])
        ->and($notMine->readers()->count())->toBe(0);
});
