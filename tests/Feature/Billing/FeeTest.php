<?php

use App\Enums\Core\GradeLevel;
use App\Filament\Gestion\Resources\Billing\Fees\Pages\CreateFee;
use App\Filament\Gestion\Resources\Billing\Fees\Pages\ListFees;
use App\Filament\Gestion\Resources\Core\Enrollments\Pages\CreateEnrollment;
use App\Models\Billing\Fee;
use App\Models\Core\Enrollment;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\Core\Student;
use App\Models\User;
use App\Services\Billing\StudentAccount;
use Filament\Actions\ReplicateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->schoolYear = SchoolYear::factory()->current()->create();
    $this->sixieme = SchoolClass::factory()->for($this->schoolYear)->create(['level' => GradeLevel::Sixieme, 'section' => '1']);
    $this->enrollment = Enrollment::factory()->for($this->sixieme)->create();
});

test('a student owes the fees of their level, the fees of every level and their optional fees', function () {
    createFee($this->schoolYear, GradeLevel::Sixieme, 'Frais de scolarité', ['2027-01-15' => 50000, '2026-10-15' => 60000, '2027-04-15' => 40000]);
    createFee($this->schoolYear, null, 'Inscription', ['2026-09-15' => 15000]);
    $canteen = createFee($this->schoolYear, null, 'Cantine', ['2026-10-01' => 30000], isOptional: true);
    createFee($this->schoolYear, null, 'Transport', ['2026-10-01' => 25000], isOptional: true);
    createFee($this->schoolYear, GradeLevel::Cinquieme, 'Frais de scolarité', ['2026-10-15' => 70000]);
    createFee(SchoolYear::factory()->create(), GradeLevel::Sixieme, 'Frais de scolarité', ['2025-10-15' => 99000]);

    $this->enrollment->optionalFees()->attach($canteen);

    $account = new StudentAccount($this->enrollment);

    expect($account->dues->pluck('label')->all())->toBe([
        'Inscription',
        'Cantine',
        'Frais de scolarité — 1re tranche',
        'Frais de scolarité — 2e tranche',
        'Frais de scolarité — 3e tranche',
    ])
        ->and($account->dues->pluck('amount')->all())->toBe([15000, 30000, 60000, 50000, 40000])
        ->and($account->total())->toBe(195000)
        ->and($account->dueBy(Carbon::parse('2026-10-15')))->toBe(105000);
});

test('amounts are written in FCFA with thousands separated', function () {
    expect(StudentAccount::formatAmount(150000))->toBe('150 000 FCFA');
});

test('staff create a fee with its installments', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(CreateFee::class)
        ->fillForm([
            'school_year_id' => $this->schoolYear->id,
            'name' => 'Frais de scolarité',
            'level' => GradeLevel::Sixieme->value,
            'installments' => [
                ['due_on' => '2026-10-15', 'amount' => 60000],
                ['due_on' => '2027-01-15', 'amount' => 50000],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Fee::sole())
        ->level->toBe(GradeLevel::Sixieme)
        ->is_optional->toBeFalse()
        ->amount->toBe(110000);
});

test('a fee is copied to another level with its installments', function () {
    $this->actingAs(User::factory()->create());
    $fee = createFee($this->schoolYear, GradeLevel::Sixieme, 'Frais de scolarité', ['2026-10-15' => 60000, '2027-01-15' => 50000]);

    Livewire::test(ListFees::class)
        ->callAction(TestAction::make(ReplicateAction::class)->table($fee), data: ['level' => GradeLevel::Cinquieme->value]);

    $copy = Fee::where('level', GradeLevel::Cinquieme)->sole();

    expect($copy->name)->toBe('Frais de scolarité')
        ->and($copy->installments->pluck('amount')->all())->toBe([60000, 50000]);
});

test('optional fees are chosen when enrolling a student', function () {
    $this->actingAs(User::factory()->create());
    $canteen = createFee($this->schoolYear, null, 'Cantine', ['2026-10-01' => 30000], isOptional: true);
    $student = Student::factory()->create();

    Livewire::test(CreateEnrollment::class)
        ->fillForm([
            'student_id' => $student->id,
            'school_class_id' => $this->sixieme->id,
            'enrolled_on' => '2026-09-14',
            'optionalFees' => [$canteen->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Enrollment::where('student_id', $student->id)->sole()->optionalFees->pluck('id')->all())->toBe([$canteen->id]);
});

test('teachers cannot manage fees', function () {
    $this->actingAs(User::factory()->teacher()->create());

    $this->get(ListFees::getUrl())->assertForbidden();
});
