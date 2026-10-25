<?php

use App\Filament\Gestion\Resources\Core\Enrollments\Pages\CreateEnrollment;
use App\Filament\Gestion\Resources\Core\Enrollments\Pages\EditEnrollment;
use App\Filament\Gestion\Resources\Core\Enrollments\Pages\ListEnrollments;
use App\Filament\Gestion\Resources\Core\SchoolClasses\Pages\EditSchoolClass;
use App\Filament\Gestion\Resources\Core\Students\Pages\EditStudent;
use App\Filament\Gestion\Resources\Core\Students\RelationManagers\EnrollmentsRelationManager;
use App\Models\Core\Enrollment;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
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

test('the school year of an enrollment always follows its class', function () {
    $class = SchoolClass::factory()->create();
    $otherClass = SchoolClass::factory()->create();

    $enrollment = Enrollment::factory()->for($class)->create();

    expect($enrollment->school_year_id)->toBe($class->school_year_id);

    $enrollment->update(['school_class_id' => $otherClass->id]);

    expect($enrollment->fresh()->school_year_id)->toBe($otherClass->school_year_id);
});

test('staff can enroll a student in a class', function () {
    $student = Student::factory()->create();
    $class = SchoolClass::factory()->create();

    Livewire::test(CreateEnrollment::class)
        ->fillForm([
            'student_id' => $student->id,
            'school_class_id' => $class->id,
            'enrolled_on' => '2026-09-14',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Enrollment::sole())
        ->student_id->toBe($student->id)
        ->school_class_id->toBe($class->id)
        ->school_year_id->toBe($class->school_year_id);
});

test('a student cannot be enrolled twice in the same school year', function () {
    $schoolYear = SchoolYear::factory()->create();
    $student = Student::factory()->create();
    Enrollment::factory()->for($student)->for(SchoolClass::factory()->for($schoolYear))->create();

    Livewire::test(CreateEnrollment::class)
        ->fillForm([
            'student_id' => $student->id,
            'school_class_id' => SchoolClass::factory()->for($schoolYear)->create()->id,
            'enrolled_on' => '2026-09-14',
        ])
        ->call('create')
        ->assertHasFormErrors(['school_class_id']);

    expect(Enrollment::count())->toBe(1);
});

test('a student can be enrolled again in a different school year', function () {
    $student = Student::factory()->create();
    Enrollment::factory()->for($student)->create();

    Livewire::test(CreateEnrollment::class)
        ->fillForm([
            'student_id' => $student->id,
            'school_class_id' => SchoolClass::factory()->create()->id,
            'enrolled_on' => '2026-09-14',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect($student->enrollments()->count())->toBe(2);
});

test('a full class refuses new enrollments', function () {
    $class = SchoolClass::factory()->create(['capacity' => 1]);
    Enrollment::factory()->for($class)->create();

    Livewire::test(CreateEnrollment::class)
        ->fillForm([
            'student_id' => Student::factory()->create()->id,
            'school_class_id' => $class->id,
            'enrolled_on' => '2026-09-14',
        ])
        ->call('create')
        ->assertHasFormErrors(['school_class_id']);
});

test('an enrollment in a full class can still be edited', function () {
    $class = SchoolClass::factory()->create(['capacity' => 1]);
    $enrollment = Enrollment::factory()->for($class)->create();

    Livewire::test(EditEnrollment::class, ['record' => $enrollment->getRouteKey()])
        ->fillForm(['enrolled_on' => '2026-09-20'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($enrollment->fresh()->enrolled_on->toDateString())->toBe('2026-09-20');
});

test('staff can enroll a student from the student page', function () {
    $student = Student::factory()->create();
    $class = SchoolClass::factory()->create();

    Livewire::test(EnrollmentsRelationManager::class, ['ownerRecord' => $student, 'pageClass' => EditStudent::class])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'school_class_id' => $class->id,
            'enrolled_on' => '2026-09-14',
        ])
        ->assertHasNoFormErrors();

    expect($student->enrollments()->sole()->school_class_id)->toBe($class->id);
});

test('the student page refuses a second enrollment in the same school year', function () {
    $student = Student::factory()->create();
    $enrollment = Enrollment::factory()->for($student)->create();
    $sameYearClass = SchoolClass::factory()->create(['school_year_id' => $enrollment->school_year_id]);

    Livewire::test(EnrollmentsRelationManager::class, ['ownerRecord' => $student, 'pageClass' => EditStudent::class])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'school_class_id' => $sameYearClass->id,
            'enrolled_on' => '2026-09-14',
        ])
        ->assertHasFormErrors(['school_class_id']);
});

test('the enrollment list shows the current school year by default', function () {
    $currentYear = SchoolYear::factory()->current()->create();
    $current = Enrollment::factory()->for(SchoolClass::factory()->for($currentYear))->create();
    $old = Enrollment::factory()->create();

    Livewire::test(ListEnrollments::class)
        ->assertCanSeeTableRecords([$current])
        ->assertCanNotSeeTableRecords([$old]);
});

test('students and classes with enrollments cannot be deleted', function () {
    $enrollment = Enrollment::factory()->create();

    Livewire::test(EditStudent::class, ['record' => $enrollment->student->getRouteKey()])
        ->assertActionHidden(DeleteAction::class);

    Livewire::test(EditSchoolClass::class, ['record' => $enrollment->schoolClass->getRouteKey()])
        ->assertActionHidden(DeleteAction::class);
});
