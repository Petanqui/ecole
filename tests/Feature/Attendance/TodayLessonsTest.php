<?php

use App\Enums\Core\GradeLevel;
use App\Enums\Timetable\Weekday;
use App\Filament\Gestion\Resources\Attendance\AttendanceSessions\AttendanceSessionResource;
use App\Filament\Gestion\Resources\Attendance\AttendanceSessions\Widgets\TodayLessons;
use App\Models\Attendance\AttendanceSession;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\Grades\ClassSubject;
use App\Models\Grades\Subject;
use App\Models\Timetable\TimetableSlot;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-26 07:45'));

    $this->schoolYear = SchoolYear::factory()->current()->create();
    $this->sixieme = SchoolClass::factory()->for($this->schoolYear)->create(['level' => GradeLevel::Sixieme, 'section' => '1']);
    $this->teacher = User::factory()->teacher()->create();
    $this->maths = ClassSubject::factory()->for($this->sixieme)->for(Subject::factory()->state(['name' => 'Mathématiques']))->create(['teacher_id' => $this->teacher->id]);
    $this->english = ClassSubject::factory()->for($this->sixieme)->for(Subject::factory()->state(['name' => 'Anglais']))->create();

    $this->mondayMaths = TimetableSlot::factory()->for($this->maths)->create(['weekday' => Weekday::Monday, 'starts_at' => '08:00', 'hours' => 2]);
    $this->mondayEnglish = TimetableSlot::factory()->for($this->english)->create(['weekday' => Weekday::Monday, 'starts_at' => '10:00']);
    $this->tuesdayMaths = TimetableSlot::factory()->for($this->maths)->create(['weekday' => Weekday::Tuesday, 'starts_at' => '08:00']);
});

test('a teacher sees their own lessons of the day, staff every lesson', function () {
    $this->actingAs($this->teacher);

    Livewire::test(TodayLessons::class)
        ->assertCanSeeTableRecords([$this->mondayMaths])
        ->assertCanNotSeeTableRecords([$this->mondayEnglish, $this->tuesdayMaths])
        ->assertTableColumnStateSet('roll_call', 'À faire', $this->mondayMaths);

    $this->actingAs(User::factory()->create());

    Livewire::test(TodayLessons::class)
        ->assertCanSeeTableRecords([$this->mondayMaths, $this->mondayEnglish], inOrder: true)
        ->assertCanNotSeeTableRecords([$this->tuesdayMaths]);
});

test('taking the roll call of a lesson fills it from the timetable, once', function () {
    $this->actingAs($this->teacher);

    $component = Livewire::test(TodayLessons::class)
        ->callAction(TestAction::make('takeRollCall')->table($this->mondayMaths));

    $session = AttendanceSession::sole();
    $component->assertRedirect(AttendanceSessionResource::getUrl('take', ['record' => $session]));
    expect($session)
        ->class_subject_id->toBe($this->maths->id)
        ->term_id->toBe($this->schoolYear->terms->first()->id)
        ->held_on->toDateString()->toBe('2026-10-26')
        ->time_slot->toBe('08:00 – 10:00')
        ->taken_by->toBe($this->teacher->id);

    Livewire::test(TodayLessons::class)
        ->assertTableColumnStateSet('roll_call', 'Fait', $this->mondayMaths)
        ->callAction(TestAction::make('takeRollCall')->table($this->mondayMaths))
        ->assertRedirect(AttendanceSessionResource::getUrl('take', ['record' => $session]));

    expect(AttendanceSession::count())->toBe(1);
});

test('no roll call is created over another one of the class, nor once every term is closed', function () {
    $this->actingAs(User::factory()->create());
    AttendanceSession::factory()->for($this->english)->for($this->schoolYear->terms->first())->create(['held_on' => '2026-10-26', 'starts_at' => '09:00']);

    Livewire::test(TodayLessons::class)
        ->callAction(TestAction::make('takeRollCall')->table($this->mondayMaths))
        ->assertNotified('La classe 6e1 a déjà un appel sur ce créneau.');

    $this->schoolYear->terms()->update(['is_closed' => true]);

    Livewire::test(TodayLessons::class)
        ->callAction(TestAction::make('takeRollCall')->table($this->mondayEnglish))
        ->assertNotified('Tous les trimestres sont clôturés : l\'appel ne peut plus être fait.');

    expect(AttendanceSession::count())->toBe(1);
});
