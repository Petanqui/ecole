<?php

use App\Enums\Attendance\AttendanceStatus;
use App\Enums\Core\GradeLevel;
use App\Filament\Gestion\Resources\Attendance\Attendances\Pages\ManageAttendances;
use App\Filament\Gestion\Resources\Core\Enrollments\Pages\EditEnrollment;
use App\Filament\Gestion\Resources\Core\Enrollments\RelationManagers\AttendancesRelationManager;
use App\Filament\Portail\Widgets\MyChildren;
use App\Models\Attendance\Attendance;
use App\Models\Attendance\AttendanceSession;
use App\Models\Core\Enrollment;
use App\Models\Core\Guardian;
use App\Models\Grades\ClassSubject;
use App\Models\Grades\Term;
use App\Models\User;
use App\Services\Attendance\AttendanceSummary;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    [$this->schoolClass, $students, $this->firstTerm] = classWithStudents(GradeLevel::Sixieme, 2);
    $this->secondTerm = $this->schoolClass->schoolYear->terms->firstWhere('number', 2);
    $this->schoolClass->schoolYear->update(['is_current' => true]);
    $this->maths = addSubject($this->schoolClass, coefficient: 3);
    [$this->awa, $this->yao] = $this->schoolClass->enrollments()->orderBy('id')->get()->all();
});

function noteAttendance(ClassSubject $classSubject, Term $term, Enrollment $enrollment, AttendanceStatus $status, int $hours = 1, string $heldOn = '2026-10-20'): Attendance
{
    $session = AttendanceSession::query()->where('class_subject_id', $classSubject->id)->whereDate('held_on', $heldOn)->first()
        ?? AttendanceSession::factory()->for($classSubject)->for($term)->create(['hours' => $hours, 'held_on' => $heldOn]);
    $session->mark($enrollment, $status);

    return $session->attendances()->where('enrollment_id', $enrollment->id)->sole();
}

test('absence hours, justified hours and late arrivals add up by term and for the year', function () {
    $staff = User::factory()->create();
    noteAttendance($this->maths, $this->firstTerm, $this->awa, AttendanceStatus::Absent, hours: 2, heldOn: '2026-10-19')->justify($staff, 'Certificat médical');
    noteAttendance($this->maths, $this->firstTerm, $this->awa, AttendanceStatus::Absent, hours: 3, heldOn: '2026-10-20');
    noteAttendance($this->maths, $this->firstTerm, $this->awa, AttendanceStatus::Late, hours: 2, heldOn: '2026-10-21');
    noteAttendance($this->maths, $this->secondTerm, $this->awa, AttendanceStatus::Absent, hours: 1, heldOn: '2027-01-12');

    $firstTerm = AttendanceSummary::forEnrollment($this->awa, $this->firstTerm);
    $year = AttendanceSummary::forEnrollment($this->awa);

    expect($firstTerm)
        ->absenceHours->toBe(5)
        ->justifiedHours->toBe(2)
        ->lateArrivals->toBe(1)
        ->and($firstTerm->unjustifiedHours())->toBe(3)
        ->and($firstTerm->describe())->toBe("5 h d'absence dont 2 h justifiée(s) — 1 retard")
        ->and($year->absenceHours)->toBe(6)
        ->and(AttendanceSummary::forEnrollment($this->yao)->describe())->toBe('Aucune absence — aucun retard');
});

test('staff justify several absences at once, and can take a justification back', function () {
    $first = noteAttendance($this->maths, $this->firstTerm, $this->awa, AttendanceStatus::Absent, heldOn: '2026-10-20');
    $second = noteAttendance($this->maths, $this->firstTerm, $this->awa, AttendanceStatus::Absent, heldOn: '2026-10-21');
    $other = noteAttendance($this->maths, $this->firstTerm, $this->yao, AttendanceStatus::Late);
    $this->actingAs($staff = User::factory()->create());

    Livewire::test(ManageAttendances::class)
        ->assertCanSeeTableRecords([$second, $first, $other], inOrder: true)
        ->selectTableRecords([$first->getKey(), $second->getKey()])
        ->callAction(TestAction::make('justify')->table()->bulk(), data: ['reason' => 'Certificat médical du 20/10'])
        ->assertHasNoActionErrors()
        ->filterTable('justified', false)
        ->assertCanSeeTableRecords([$other])
        ->assertCanNotSeeTableRecords([$first, $second]);

    expect($first->fresh())
        ->isJustified()->toBeTrue()
        ->justification->toBe('Certificat médical du 20/10')
        ->justified_by->toBe($staff->id)
        ->and($other->fresh()->isJustified())->toBeFalse();

    Livewire::test(ManageAttendances::class)->callAction(TestAction::make('unjustify')->table($first));

    expect($first->fresh()->isJustified())->toBeFalse();
});

test('a justification survives a change between absent and late', function () {
    $attendance = noteAttendance($this->maths, $this->firstTerm, $this->awa, AttendanceStatus::Absent);
    $attendance->justify(User::factory()->create(), 'Rendez-vous médical');

    $attendance->session->mark($this->awa, AttendanceStatus::Late);

    expect($attendance->fresh())
        ->status->toBe(AttendanceStatus::Late)
        ->isJustified()->toBeTrue();
});

test('the enrollment shows the absences of the student with the totals of each term', function () {
    $own = noteAttendance($this->maths, $this->firstTerm, $this->awa, AttendanceStatus::Absent, hours: 2);
    $other = noteAttendance($this->maths, $this->firstTerm, $this->yao, AttendanceStatus::Absent);
    $this->actingAs(User::factory()->create());

    Livewire::test(AttendancesRelationManager::class, ['ownerRecord' => $this->awa, 'pageClass' => EditEnrollment::class])
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$other])
        ->assertSee("1er trimestre : 2 h d'absence — aucun retard");
});

test('a parent sees the absences of their child with their justification', function () {
    noteAttendance($this->maths, $this->firstTerm, $this->awa, AttendanceStatus::Absent, hours: 2, heldOn: '2026-10-19')
        ->justify(User::factory()->create(), 'Certificat médical');
    noteAttendance($this->maths, $this->firstTerm, $this->awa, AttendanceStatus::Absent, hours: 1, heldOn: '2026-10-20');
    noteAttendance($this->maths, $this->firstTerm, $this->awa, AttendanceStatus::Late, heldOn: '2026-10-21');
    $parent = User::factory()->guardian()->create();
    Guardian::factory()->for($this->awa->student->family)->for($parent)->create();

    Filament::setCurrentPanel('portail');
    $this->actingAs($parent);

    Livewire::test(MyChildren::class)
        ->assertSee('3 h')
        ->assertSee('dont 1 h non justifiée(s) · 1 retard')
        ->mountAction(TestAction::make('attendance')->table($this->awa->student))
        ->assertMountedActionModalSee(['Certificat médical', 'Non justifiée', '19/10/2026', 'En retard']);
});

test('teachers cannot see the absences list', function () {
    $this->actingAs(User::factory()->teacher()->create());

    $this->get(ManageAttendances::getUrl())->assertForbidden();
});
