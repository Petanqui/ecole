<?php

use App\Enums\Core\GradeLevel;
use App\Enums\Timetable\Weekday;
use App\Filament\Gestion\Pages\Timetable\ClassTimetablePage;
use App\Filament\Gestion\Pages\Timetable\TeacherTimetablePage;
use App\Filament\Portail\Widgets\MyChildren;
use App\Models\Core\Enrollment;
use App\Models\Core\Guardian;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\Grades\ClassSubject;
use App\Models\Grades\Subject;
use App\Models\Timetable\Room;
use App\Models\Timetable\TimetableSlot;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $schoolYear = SchoolYear::factory()->current()->create();
    $this->sixieme = SchoolClass::factory()->for($schoolYear)->create(['level' => GradeLevel::Sixieme, 'section' => '1']);
    $this->cinquieme = SchoolClass::factory()->for($schoolYear)->create(['level' => GradeLevel::Cinquieme, 'section' => '1']);
    $this->teacher = User::factory()->teacher()->create(['name' => 'Jean Kouassi']);

    $maths = Subject::factory()->create(['name' => 'Mathématiques']);
    $this->sixiemeMaths = ClassSubject::factory()->for($this->sixieme)->for($maths)->create(['teacher_id' => $this->teacher->id]);
    $this->cinquiemeMaths = ClassSubject::factory()->for($this->cinquieme)->for($maths)->create(['teacher_id' => $this->teacher->id]);
    $this->sixiemeEnglish = ClassSubject::factory()->for($this->sixieme)->for(Subject::factory()->state(['name' => 'Anglais']))->create();
    $this->cinquiemeEnglish = ClassSubject::factory()->for($this->cinquieme)->for(Subject::factory()->state(['name' => 'Anglais']))->create();
    $this->lab = Room::factory()->create(['name' => 'Labo']);

    TimetableSlot::factory()->for($this->sixiemeMaths)->for($this->lab)->create(['weekday' => Weekday::Monday, 'starts_at' => '08:00', 'hours' => 2]);
});

test('a class, a teacher or a room cannot have two lessons at the same time', function () {
    $conflicts = fn (ClassSubject $classSubject, string $startsAt, ?Room $room = null, Weekday $weekday = Weekday::Monday) => TimetableSlot::conflicts($classSubject, $weekday, $startsAt, 1, $room?->id);

    expect($conflicts($this->sixiemeEnglish, '09:00'))->toBe(['La classe 6e1 a déjà Mathématiques le lundi de 08:00 à 10:00.'])
        ->and($conflicts($this->cinquiemeMaths, '09:00'))->toBe(['Jean Kouassi a déjà Mathématiques en 6e1 le lundi de 08:00 à 10:00.'])
        ->and($conflicts($this->cinquiemeEnglish, '09:00', $this->lab))->toBe(['La salle Labo est déjà prise par Mathématiques en 6e1 le lundi de 08:00 à 10:00.'])
        ->and($conflicts($this->cinquiemeEnglish, '09:00'))->toBe([])
        ->and($conflicts($this->sixiemeEnglish, '10:00', $this->lab))->toBe([])
        ->and($conflicts($this->sixiemeEnglish, '09:00', weekday: Weekday::Tuesday))->toBe([]);
});

test('staff build the week of a class, and lessons that clash are refused', function () {
    $this->actingAs(User::factory()->create());

    $add = fn (array $data) => Livewire::test(ClassTimetablePage::class)
        ->set('selection.school_class_id', $this->sixieme->id)
        ->callAction(TestAction::make('create')->table(), data: [
            'class_subject_id' => $this->sixiemeEnglish->id,
            'weekday' => Weekday::Monday->value,
            'hours' => 1,
            ...$data,
        ]);

    $add(['starts_at' => '09:00'])->assertHasActionErrors(['starts_at']);
    $add(['starts_at' => '10:00', 'room_id' => $this->lab->id])->assertHasNoActionErrors();

    expect($this->sixiemeEnglish->timetableSlots()->sole())
        ->time_slot->toBe('10:00 – 11:00')
        ->room_id->toBe($this->lab->id);

    Livewire::test(ClassTimetablePage::class)
        ->set('selection.school_class_id', $this->sixieme->id)
        ->assertSee(['Lundi', '08:00 – 10:00', 'Mathématiques', 'Jean Kouassi', 'Anglais', 'Labo']);
});

test('changing a lesson does not clash with itself', function () {
    $this->actingAs(User::factory()->create());
    $lesson = $this->sixiemeMaths->timetableSlots()->sole();

    Livewire::test(ClassTimetablePage::class)
        ->set('selection.school_class_id', $this->sixieme->id)
        ->callAction(TestAction::make('edit')->table($lesson), data: ['starts_at' => '09:00'])
        ->assertHasNoActionErrors();

    expect($lesson->fresh()->time_slot)->toBe('09:00 – 11:00');
});

test('a teacher sees their own week across classes', function () {
    TimetableSlot::factory()->for($this->cinquiemeMaths)->create(['weekday' => Weekday::Tuesday, 'starts_at' => '10:00', 'hours' => 1]);
    TimetableSlot::factory()->for($this->sixiemeEnglish)->create(['weekday' => Weekday::Tuesday, 'starts_at' => '08:00']);
    $this->actingAs($this->teacher);

    Livewire::test(TeacherTimetablePage::class)
        ->assertSee(['6e1', '5e1', '08:00 – 10:00', '10:00 – 11:00', '3 heure(s) de cours par semaine'])
        ->assertDontSee('Anglais');
});

test('staff look at the week of any teacher', function () {
    $this->actingAs(User::factory()->create());
    $otherTeacher = User::factory()->teacher()->create(['name' => 'Awa Diallo']);
    $this->sixiemeEnglish->update(['teacher_id' => $otherTeacher->id]);
    TimetableSlot::factory()->for($this->sixiemeEnglish)->create(['weekday' => Weekday::Friday, 'starts_at' => '14:00']);

    Livewire::test(TeacherTimetablePage::class)
        ->set('selection.teacher_id', $otherTeacher->id)
        ->assertSee(['Anglais', '14:00 – 15:00'])
        ->assertDontSee('Mathématiques');
});

test('the class timetable is printed as PDF by staff and by the parents of the class', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('filament.gestion.timetables.class', $this->sixieme))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $parent = User::factory()->guardian()->create();
    $student = Enrollment::factory()->for($this->sixieme)->create()->student;
    Guardian::factory()->for($student->family)->for($parent)->create();

    $this->actingAs($parent)->get(route('filament.portail.timetables.class', $this->sixieme))->assertOk();
    $this->actingAs($parent)->get(route('filament.portail.timetables.class', $this->cinquieme))->assertForbidden();
});

test('a parent sees the timetable of their child in the portal', function () {
    $parent = User::factory()->guardian()->create();
    $student = Enrollment::factory()->for($this->sixieme)->create()->student;
    Guardian::factory()->for($student->family)->for($parent)->create();
    Filament::setCurrentPanel('portail');
    $this->actingAs($parent);

    Livewire::test(MyChildren::class)
        ->mountAction(TestAction::make('timetable')->table($student))
        ->assertMountedActionModalSee(['Lundi', '08:00 – 10:00', 'Mathématiques', 'Jean Kouassi', 'Labo', 'Télécharger en PDF']);
});

test('teachers cannot edit the timetables', function () {
    $this->actingAs($this->teacher);

    $this->get(ClassTimetablePage::getUrl())->assertForbidden();
});
