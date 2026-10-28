<?php

use App\Enums\Billing\PaymentMethod;
use App\Enums\Core\GradeLevel;
use App\Filament\Gestion\Resources\Billing\Payments\Pages\ManagePayments;
use App\Filament\Gestion\Resources\Core\Enrollments\Pages\EditEnrollment;
use App\Filament\Gestion\Resources\Core\Enrollments\RelationManagers\PaymentsRelationManager;
use App\Models\Billing\Payment;
use App\Models\Core\Enrollment;
use App\Models\Core\Guardian;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\User;
use App\Services\Billing\StudentAccount;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->schoolYear = SchoolYear::factory()->current()->create();
    $sixieme = SchoolClass::factory()->for($this->schoolYear)->create(['level' => GradeLevel::Sixieme, 'section' => '1']);
    $this->enrollment = Enrollment::factory()->for($sixieme)->create();

    createFee($this->schoolYear, null, 'Inscription', ['2026-09-15' => 15000]);
    createFee($this->schoolYear, GradeLevel::Sixieme, 'Frais de scolarité', ['2026-10-15' => 60000, '2027-01-15' => 50000]);
});

function pay(Enrollment $enrollment, int $amount, string $paidOn = '2026-10-01'): Payment
{
    return Payment::factory()->for($enrollment)->create(['amount' => $amount, 'paid_on' => $paidOn]);
}

test('receipt numbers follow each other within a year', function () {
    expect(pay($this->enrollment, 5000)->receipt_number)->toBe('REC-'.now()->year.'-00001')
        ->and(pay($this->enrollment, 5000)->receipt_number)->toBe('REC-'.now()->year.'-00002');
});

test('payments settle the oldest installments first', function () {
    pay($this->enrollment, 30000);
    pay($this->enrollment, 10000);

    $account = new StudentAccount($this->enrollment);

    expect($account->dues->pluck('remaining')->all())->toBe([0, 35000, 50000])
        ->and($account->paid())->toBe(40000)
        ->and($account->balance())->toBe(85000)
        ->and($account->overdue(Carbon::parse('2026-11-01')))->toBe(35000)
        ->and($account->overdue(Carbon::parse('2026-09-01')))->toBe(0);
});

test('a cancelled payment no longer counts', function () {
    $payment = pay($this->enrollment, 30000);
    $payment->cancel(User::factory()->admin()->create(), 'Erreur de saisie');

    expect((new StudentAccount($this->enrollment))->paid())->toBe(0)
        ->and($payment->fresh())->isCancelled()->toBeTrue();
});

test('staff record a payment and are named on it', function () {
    $this->actingAs($cashier = User::factory()->create());

    Livewire::test(ManagePayments::class)
        ->callAction(CreateAction::class, data: [
            'enrollment_id' => $this->enrollment->id,
            'amount' => 25000,
            'paid_on' => '2026-10-01',
            'method' => PaymentMethod::OrangeMoney->value,
            'reference' => 'MP261001.1234.A56789',
            'payer_name' => 'M. Koné',
        ])
        ->assertHasNoActionErrors();

    expect(Payment::sole())
        ->amount->toBe(25000)
        ->method->toBe(PaymentMethod::OrangeMoney)
        ->received_by->toBe($cashier->id);
});

test('the student is found by full name in any order, and listed before any search', function () {
    $this->actingAs(User::factory()->create());
    $student = $this->enrollment->student;
    $student->update(['last_name' => 'Koné', 'first_name' => 'Awa']);
    $other = Enrollment::factory()->for($this->enrollment->schoolClass)->create();

    $select = Livewire::test(ManagePayments::class)
        ->mountAction(CreateAction::class)
        ->instance()
        ->getSchemaComponent('mountedActionSchema0.enrollment_id');

    expect(array_keys($select->getSearchResults('awa kon')))->toBe([$this->enrollment->id])
        ->and(array_keys($select->getSearchResults('Koné Awa')))->toBe([$this->enrollment->id])
        ->and($select->getSearchResults('awa yao'))->toBe([])
        ->and($select->getOptions())->toHaveKeys([$this->enrollment->id, $other->id]);
});

test('a mobile money payment needs its transaction id', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ManagePayments::class)
        ->callAction(CreateAction::class, data: [
            'enrollment_id' => $this->enrollment->id,
            'amount' => 25000,
            'paid_on' => '2026-10-01',
            'method' => PaymentMethod::Wave->value,
        ])
        ->assertHasActionErrors(['reference' => 'required']);
});

test('a payment cannot exceed what is left to pay', function () {
    $this->actingAs(User::factory()->create());
    pay($this->enrollment, 100000);

    Livewire::test(ManagePayments::class)
        ->callAction(CreateAction::class, data: [
            'enrollment_id' => $this->enrollment->id,
            'amount' => 30000,
            'paid_on' => '2026-10-01',
            'method' => PaymentMethod::Cash->value,
        ])
        ->assertHasActionErrors(['amount']);

    expect(Payment::count())->toBe(1);
});

test('staff record a payment from the account of a student', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(PaymentsRelationManager::class, ['ownerRecord' => $this->enrollment, 'pageClass' => EditEnrollment::class])
        ->assertSee('Reste : 125 000 FCFA')
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'amount' => 15000,
            'paid_on' => '2026-10-01',
            'method' => PaymentMethod::Cash->value,
        ])
        ->assertHasNoActionErrors();

    expect($this->enrollment->payments()->sole()->amount)->toBe(15000);
});

test('only administrators cancel a payment', function () {
    $payment = pay($this->enrollment, 15000);

    $this->actingAs(User::factory()->create());
    Livewire::test(ManagePayments::class)->assertActionHidden(TestAction::make('cancel')->table($payment));

    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(ManagePayments::class)
        ->callAction(TestAction::make('cancel')->table($payment), data: ['reason' => 'Doublon']);

    expect($payment->fresh())
        ->isCancelled()->toBeTrue()
        ->cancellation_reason->toBe('Doublon');
});

test('the receipt shows the amount in words and what is left to pay', function () {
    $this->actingAs(User::factory()->create());
    pay($this->enrollment, 15000, '2026-09-10');
    $payment = pay($this->enrollment, 60000, '2026-10-10');

    $this->get(route('filament.gestion.receipts.show', $payment))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $html = view('billing.receipt', ['payment' => $payment, 'total' => 125000, 'discount' => 0, 'paidToDate' => 75000])->render();

    expect($html)
        ->toContain($payment->receipt_number)
        ->toContain('60 000 FCFA')
        ->toContain('soixante mille francs CFA')
        ->toContain('50 000 FCFA');
});

test('a parent may download the receipts of their own children only', function () {
    $payment = pay($this->enrollment, 15000);
    $parent = User::factory()->guardian()->create();
    Guardian::factory()->for($this->enrollment->student->family)->for($parent)->create();
    $otherParent = User::factory()->guardian()->create();
    Guardian::factory()->for($otherParent)->create();

    expect($parent->can('download-receipt', $payment))->toBeTrue()
        ->and($otherParent->can('download-receipt', $payment))->toBeFalse()
        ->and(User::factory()->teacher()->create()->can('download-receipt', $payment))->toBeFalse();
});
