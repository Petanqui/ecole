<?php

use App\Enums\Attendance\AttendanceStatus;
use App\Enums\Core\GradeLevel;
use App\Filament\Gestion\Resources\Attendance\AttendanceSessions\AttendanceSessionResource;
use App\Filament\Gestion\Resources\Attendance\AttendanceSessions\Pages\ManageAttendanceSessions;
use App\Filament\Gestion\Resources\Attendance\AttendanceSessions\Pages\TakeAttendance;
use App\Models\Attendance\AttendanceSession;
use App\Models\Core\Enrollment;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\User;
use Database\Seeders\SubjectSeeder;
use Filament\Actions\CreateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SubjectSeeder::class);

    $this->teacher = User::factory()->teacher()->create();
    $this->schoolClass = SchoolClass::factory()
        ->for(SchoolYear::factory()->current())
        ->create(['level' => GradeLevel::Sixieme, 'section' => '1']);
    $this->maths = $this->schoolClass->classSubjects()->whereRelation('subject', 'code', 'MATH')->sole();
    $this->maths->update(['teacher_id' => $this->teacher->id]);
    $this->english = $this->schoolClass->classSubjects()->whereRelation('subject', 'code', 'ANG')->sole();
    $this->firstTerm = $this->schoolClass->schoolYear->terms->first();

    $this->enrollments = Enrollment::factory()->count(3)->for($this->schoolClass)->create();
});

test('a teacher records a lesson and lands on its roll call', function () {
    $this->actingAs($this->teacher);

    $component = Livewire::test(ManageAttendanceSessions::class)
        ->mountAction(CreateAction::class)
        ->fillForm(['class_subject_id' => $this->maths->id])
        ->assertSchemaStateSet(['term_id' => $this->firstTerm->id])
        ->fillForm(['held_on' => '2026-10-20', 'starts_at' => '08:00', 'hours' => 2])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $session = AttendanceSession::sole();
    $component->assertRedirect(AttendanceSessionResource::getUrl('take', ['record' => $session]));
    expect($session)
        ->time_slot->toBe('08:00 – 10:00')
        ->taken_by->toBe($this->teacher->id);
});

test('a class cannot have two lessons at the same time', function () {
    AttendanceSession::factory()->for($this->english)->for($this->firstTerm)->create(['held_on' => '2026-10-20', 'starts_at' => '08:00', 'hours' => 2]);
    $this->actingAs(User::factory()->create());

    $callWith = fn (string $startsAt) => Livewire::test(ManageAttendanceSessions::class)
        ->callAction(CreateAction::class, data: [
            'class_subject_id' => $this->maths->id,
            'term_id' => $this->firstTerm->id,
            'held_on' => '2026-10-20',
            'starts_at' => $startsAt,
            'hours' => 1,
        ]);

    $callWith('09:00')->assertHasActionErrors(['starts_at']);
    $callWith('10:00')->assertHasNoActionErrors();
});

test('a teacher cannot take the roll call of a subject they do not teach', function () {
    $this->actingAs($this->teacher);

    Livewire::test(ManageAttendanceSessions::class)
        ->callAction(CreateAction::class, data: [
            'class_subject_id' => $this->english->id,
            'term_id' => $this->firstTerm->id,
            'held_on' => '2026-10-20',
            'starts_at' => '08:00',
            'hours' => 1,
        ])
        ->assertHasActionErrors(['class_subject_id']);

    $other = AttendanceSession::factory()->for($this->english)->for($this->firstTerm)->create();
    $this->get(AttendanceSessionResource::getUrl('take', ['record' => $other]))->assertNotFound();
});

test('students are present unless marked absent or late', function () {
    $session = AttendanceSession::factory()->for($this->maths)->for($this->firstTerm)->create();
    [$awa, $yao] = $this->enrollments;
    $this->actingAs($this->teacher);

    $component = Livewire::test(TakeAttendance::class, ['record' => $session->getKey()])
        ->assertCanSeeTableRecords($this->enrollments)
        ->assertTableColumnStateSet('status', AttendanceStatus::Present->value, $awa)
        ->call('updateTableColumnState', 'status', (string) $awa->getKey(), AttendanceStatus::Absent->value)
        ->call('updateTableColumnState', 'status', (string) $yao->getKey(), AttendanceStatus::Late->value);

    expect($session->attendances()->pluck('status', 'enrollment_id')->all())->toEqual([
        $awa->id => AttendanceStatus::Absent,
        $yao->id => AttendanceStatus::Late,
    ]);

    $component->call('updateTableColumnState', 'status', (string) $yao->getKey(), AttendanceStatus::Present->value);

    expect($session->attendances()->pluck('enrollment_id')->all())->toBe([$awa->id]);
});

test('a teacher can no longer change a roll call once the term is closed', function () {
    $session = AttendanceSession::factory()->for($this->maths)->for($this->firstTerm)->create();
    $this->firstTerm->update(['is_closed' => true]);
    $this->actingAs($this->teacher);

    Livewire::test(TakeAttendance::class, ['record' => $session->getKey()])
        ->assertActionHidden('everyonePresent')
        ->call('updateTableColumnState', 'status', (string) $this->enrollments->first()->getKey(), AttendanceStatus::Absent->value);

    expect($session->attendances()->count())->toBe(0);
});
