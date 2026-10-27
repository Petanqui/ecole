<?php

use App\Enums\Core\GradeLevel;
use App\Filament\Gestion\Pages\Billing\ArrearsPage;
use App\Filament\Portail\Widgets\MyChildren;
use App\Models\Billing\Payment;
use App\Models\Core\Enrollment;
use App\Models\Core\Guardian;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\Core\Student;
use App\Models\User;
use App\Services\Billing\StudentAccount;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->schoolYear = SchoolYear::factory()->current()->create();
    $this->sixieme = SchoolClass::factory()->for($this->schoolYear)->create(['level' => GradeLevel::Sixieme, 'section' => '1']);
    $this->cm1 = SchoolClass::factory()->for($this->schoolYear)->create(['level' => GradeLevel::Cm1, 'section' => 'A']);

    $this->awa = Enrollment::factory()->for(Student::factory()->state(['last_name' => 'Koné', 'first_name' => 'Awa']))->for($this->sixieme)->create();
    $this->yao = Enrollment::factory()->for(Student::factory()->state(['last_name' => 'Yao', 'first_name' => 'Kouassi']))->for($this->sixieme)->create();
    $this->fatou = Enrollment::factory()->for(Student::factory()->state(['last_name' => 'Diallo', 'first_name' => 'Fatou']))->for($this->cm1)->create();

    createFee($this->schoolYear, null, 'Inscription', ['2026-09-15' => 15000]);
    createFee($this->schoolYear, GradeLevel::Sixieme, 'Frais de scolarité', ['2026-10-15' => 60000, '2027-01-15' => 50000]);
    createFee($this->schoolYear, GradeLevel::Cm1, 'Frais de scolarité', ['2026-10-15' => 40000]);
    $this->canteen = createFee($this->schoolYear, null, 'Cantine', ['2026-10-01' => 30000], isOptional: true);
    $this->awa->optionalFees()->attach($this->canteen);

    Payment::factory()->for($this->yao)->create(['amount' => 75000]);
    Payment::factory()->for($this->fatou)->create(['amount' => 20000]);
    $this->travelTo(Carbon::parse('2026-11-02'));
});

test('accounts computed together match accounts computed one by one', function () {
    $accounts = StudentAccount::forEnrollments(Enrollment::all());

    foreach ([$this->awa, $this->yao, $this->fatou] as $enrollment) {
        $single = new StudentAccount($enrollment);

        expect($accounts[$enrollment->id]->dues->all())->toEqual($single->dues->all())
            ->and($accounts[$enrollment->id]->paid())->toBe($single->paid());
    }

    expect($accounts[$this->awa->id]->total())->toBe(155000);
});

test('the arrears page lists the students behind with their payments, the largest arrears first', function () {
    $this->actingAs(User::factory()->create());

    // Awa owes 15 000 + 30 000 + 60 000 ; Fatou 15 000 + 40 000 − 20 000 ; Yao is up to date
    Livewire::test(ArrearsPage::class)
        ->assertCanSeeTableRecords([$this->awa->id, $this->fatou->id], inOrder: true)
        ->assertCanNotSeeTableRecords([$this->yao->id])
        ->assertSee('105 000 FCFA')
        ->assertSee('Total en retard : 140 000 FCFA')
        ->set('criteria.school_class_id', $this->cm1->id)
        ->assertCanSeeTableRecords([$this->fatou->id])
        ->assertCanNotSeeTableRecords([$this->awa->id])
        ->set('criteria.school_class_id', null)
        ->set('criteria.includes_up_to_date', true)
        ->assertCanSeeTableRecords([$this->yao->id]);
});

test('the arrears are exported as CSV', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ArrearsPage::class)
        ->callAction('export')
        ->assertFileDownloaded('impayes-2026-11-02.csv');
});

test('teachers cannot see the arrears', function () {
    $this->actingAs(User::factory()->teacher()->create());

    $this->get(ArrearsPage::getUrl())->assertForbidden();
});

test('a parent sees what is left to pay and downloads the receipts of their child', function () {
    $parent = User::factory()->guardian()->create();
    Guardian::factory()->for($this->yao->student->family)->for($parent)->create();
    $receipt = $this->yao->payments()->sole();
    $otherReceipt = $this->fatou->payments()->sole();

    Filament::setCurrentPanel('portail');
    $this->actingAs($parent);

    Livewire::test(MyChildren::class)
        ->assertSee('50 000 FCFA')
        ->mountAction(TestAction::make('account')->table($this->yao->student))
        ->assertMountedActionModalSee([$receipt->receipt_number, 'Frais de scolarité — 2e tranche']);

    $this->get(route('filament.portail.receipts.show', $receipt))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
    $this->get(route('filament.portail.receipts.show', $otherReceipt))->assertForbidden();
});
