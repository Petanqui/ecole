<?php

use App\Enums\Billing\DiscountKind;
use App\Enums\Core\GradeLevel;
use App\Filament\Gestion\Resources\Billing\Discounts\Pages\ManageDiscounts;
use App\Filament\Gestion\Resources\Core\Enrollments\Pages\EditEnrollment;
use App\Models\Billing\Discount;
use App\Models\Billing\Fee;
use App\Models\Billing\Payment;
use App\Models\Core\Enrollment;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\User;
use App\Services\Billing\StudentAccount;
use Filament\Actions\CreateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->schoolYear = SchoolYear::factory()->current()->create();
    $sixieme = SchoolClass::factory()->for($this->schoolYear)->create(['level' => GradeLevel::Sixieme, 'section' => '1']);
    $this->enrollment = Enrollment::factory()->for($sixieme)->create();

    $this->registration = createFee($this->schoolYear, null, 'Inscription', ['2026-09-15' => 15000]);
    $this->tuition = createFee($this->schoolYear, GradeLevel::Sixieme, 'Frais de scolarité', ['2026-10-15' => 60000, '2027-01-15' => 50000]);
    $this->canteen = createFee($this->schoolYear, null, 'Cantine', ['2026-10-01' => 30000], isOptional: true);
});

function grant(Enrollment $enrollment, DiscountKind $kind, int $value, array $fees): Discount
{
    $discount = Discount::factory()->for($enrollment->schoolYear)->create(['kind' => $kind, 'value' => $value]);
    $discount->fees()->attach(array_map(fn (Fee $fee): int => $fee->id, $fees));
    $enrollment->discounts()->attach($discount);

    return $discount;
}

/**
 * @return array<string, int> due label => amount
 */
function dueAmounts(Enrollment $enrollment): array
{
    return (new StudentAccount($enrollment->fresh()))->dues->pluck('amount', 'label')->all();
}

test('a percentage reduces every installment of the fees it names', function () {
    grant($this->enrollment, DiscountKind::Percentage, 10, [$this->tuition]);

    $account = new StudentAccount($this->enrollment->fresh());

    expect(dueAmounts($this->enrollment))->toBe([
        'Inscription' => 15000,
        'Frais de scolarité — 1re tranche' => 54000,
        'Frais de scolarité — 2e tranche' => 45000,
    ])
        ->and($account->discount())->toBe(11000)
        ->and($account->total())->toBe(114000);
});

test('a fixed amount is shared between its fees, then between their installments', function () {
    grant($this->enrollment, DiscountKind::Amount, 25000, [$this->registration, $this->tuition]);

    // 25 000 × 15 000 / 125 000 = 3 000 on the registration, 22 000 on the tuition (12 000 + 10 000)
    expect(dueAmounts($this->enrollment))->toBe([
        'Inscription' => 12000,
        'Frais de scolarité — 1re tranche' => 48000,
        'Frais de scolarité — 2e tranche' => 40000,
    ]);
});

test('reductions add up but never exceed the fee, and skip fees the student does not owe', function () {
    grant($this->enrollment, DiscountKind::Percentage, 60, [$this->registration]);
    grant($this->enrollment, DiscountKind::Percentage, 60, [$this->registration]);
    grant($this->enrollment, DiscountKind::Amount, 30000, [$this->canteen, $this->tuition]);

    // The canteen is optional and not subscribed: the whole 30 000 goes to the tuition
    expect(dueAmounts($this->enrollment))->toBe([
        'Inscription' => 0,
        'Frais de scolarité — 1re tranche' => 43637,
        'Frais de scolarité — 2e tranche' => 36363,
    ])
        ->and((new StudentAccount($this->enrollment->fresh()))->discount())->toBe(45000);
});

test('accounts computed together apply the same reductions', function () {
    grant($this->enrollment, DiscountKind::Percentage, 10, [$this->tuition]);
    Payment::factory()->for($this->enrollment)->create(['amount' => 20000]);

    $together = StudentAccount::forEnrollments(Enrollment::all())[$this->enrollment->id];

    expect($together->dues->all())->toEqual((new StudentAccount($this->enrollment->fresh()))->dues->all());
});

test('staff create a reduction and give it to a student on their enrollment', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ManageDiscounts::class)
        ->callAction(CreateAction::class, data: [
            'school_year_id' => $this->schoolYear->id,
            'name' => 'Bourse',
            'kind' => DiscountKind::Percentage->value,
            'value' => 50,
            'fees' => [$this->tuition->id],
        ])
        ->assertHasNoActionErrors();

    $discount = Discount::sole();
    expect($discount->fees->modelKeys())->toBe([$this->tuition->id]);

    Livewire::test(EditEnrollment::class, ['record' => $this->enrollment->getKey()])
        ->fillForm(['discounts' => [$discount->id]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect((new StudentAccount($this->enrollment->fresh()))->total())->toBe(70000);
});

test('a percentage above 100 is refused', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ManageDiscounts::class)
        ->callAction(CreateAction::class, data: [
            'school_year_id' => $this->schoolYear->id,
            'name' => 'Erreur',
            'kind' => DiscountKind::Percentage->value,
            'value' => 150,
            'fees' => [$this->tuition->id],
        ])
        ->assertHasActionErrors(['value']);
});
