<?php

use App\Enums\Attendance\AttendanceStatus;
use App\Enums\Core\GradeLevel;
use App\Filament\Gestion\Widgets\AttendanceByClass;
use App\Filament\Gestion\Widgets\EnrollmentsByLevelChart;
use App\Filament\Gestion\Widgets\PaymentsByMonthChart;
use App\Filament\Gestion\Widgets\SchoolOverview;
use App\Models\Attendance\AttendanceSession;
use App\Models\Billing\Payment;
use App\Models\Core\Enrollment;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\Grades\ClassSubject;
use App\Models\User;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-10'));

    $this->schoolYear = SchoolYear::factory()->current()->create(['starts_on' => '2026-09-01', 'ends_on' => '2027-07-31']);
    $this->sixieme = SchoolClass::factory()->for($this->schoolYear)->create(['level' => GradeLevel::Sixieme, 'section' => '1']);
    $this->cm1 = SchoolClass::factory()->for($this->schoolYear)->create(['level' => GradeLevel::Cm1, 'section' => 'A']);
    $this->sixiemeStudents = Enrollment::factory()->count(2)->for($this->sixieme)->create();
    $this->cm1Student = Enrollment::factory()->for($this->cm1)->create();

    createFee($this->schoolYear, null, 'Inscription', ['2026-09-15' => 20000]);
    Payment::factory()->for($this->sixiemeStudents[0])->create(['amount' => 20000, 'paid_on' => '2026-09-20']);
    Payment::factory()->for($this->sixiemeStudents[1])->create(['amount' => 5000, 'paid_on' => '2026-11-05']);
});

/**
 * @return array<string, mixed>
 */
function chartData(string $widget): array
{
    return (fn () => $this->getData())->call(new $widget);
}

test('the overview shows enrollments, money received, arrears and recent absences', function () {
    $this->actingAs(User::factory()->create());
    $session = AttendanceSession::factory()
        ->for(ClassSubject::factory()->for($this->sixieme))
        ->for($this->schoolYear->terms->first())
        ->create(['held_on' => '2026-11-03', 'hours' => 2]);
    $session->mark($this->sixiemeStudents[0], AttendanceStatus::Absent);

    // Arrears: 15 000 (second student) + 20 000 (CM1 student)
    Livewire::test(SchoolOverview::class)
        ->assertSee(['Élèves inscrits', '3', '2 classe(s)'])
        ->assertSee(['5 000 FCFA', 'Sur l\'année : 25 000 FCFA'])
        ->assertSee(['35 000 FCFA', '2 élève(s) concerné(s)'])
        ->assertSee(['2 h', 'dont 2 h non justifiée(s)']);
});

test('the charts count students by level and money by month of the school year', function () {
    expect(chartData(EnrollmentsByLevelChart::class))
        ->labels->toBe(['CM1', '6e'])
        ->datasets->{0}->data->toBe([1, 2]);

    $payments = chartData(PaymentsByMonthChart::class);

    expect($payments['labels'])->toHaveCount(11)
        ->and(array_slice($payments['datasets'][0]['data'], 0, 3))->toBe([20000, 0, 5000]);
});

test('the attendance of each class compares hours missed with hours of roll calls', function () {
    $this->actingAs(User::factory()->create());
    $session = AttendanceSession::factory()
        ->for(ClassSubject::factory()->for($this->sixieme))
        ->for($this->schoolYear->terms->first())
        ->create(['hours' => 2]);
    $session->mark($this->sixiemeStudents[0], AttendanceStatus::Absent);
    $session->mark($this->sixiemeStudents[1], AttendanceStatus::Late);

    // 2 hours missed out of 2 hours × 2 students
    Livewire::test(AttendanceByClass::class)
        ->assertCanSeeTableRecords([$this->sixieme, $this->cm1], inOrder: true)
        ->assertTableColumnStateSet('absence_hours', 2, $this->sixieme)
        ->assertTableColumnStateSet('late_arrivals', 1, $this->sixieme)
        ->assertTableColumnFormattedStateSet('absence_rate', '50,0 %', $this->sixieme);
});

test('teachers do not see the school figures on the dashboard', function () {
    $this->actingAs(User::factory()->teacher()->create());

    expect(SchoolOverview::canView())->toBeFalse()
        ->and(AttendanceByClass::canView())->toBeFalse();

    $this->get(Dashboard::getUrl())->assertOk()->assertDontSee('L\'école en chiffres');
});
