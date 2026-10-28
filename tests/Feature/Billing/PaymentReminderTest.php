<?php

use App\Enums\Core\GradeLevel;
use App\Filament\Gestion\Pages\Billing\ArrearsPage;
use App\Models\Billing\Payment;
use App\Models\Billing\PaymentReminder;
use App\Models\Core\Enrollment;
use App\Models\Core\Guardian;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\Core\Student;
use App\Models\Messaging\Conversation;
use App\Models\User;
use App\Services\Billing\StudentAccount;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $schoolYear = SchoolYear::factory()->current()->create();
    $sixieme = SchoolClass::factory()->for($schoolYear)->create(['level' => GradeLevel::Sixieme, 'section' => '1']);

    $this->awa = Enrollment::factory()->for(Student::factory()->state(['last_name' => 'Koné', 'first_name' => 'Awa']))->for($sixieme)->create();
    $this->yao = Enrollment::factory()->for(Student::factory()->state(['last_name' => 'Yao', 'first_name' => 'Kouassi']))->for($sixieme)->create();
    $this->fatou = Enrollment::factory()->for(Student::factory()->state(['last_name' => 'Diallo', 'first_name' => 'Fatou']))->for($sixieme)->create();
    Guardian::factory()->for($this->awa->student->family)->for(User::factory()->guardian())->create();

    createFee($schoolYear, null, 'Inscription', ['2026-09-15' => 15000]);
    createFee($schoolYear, GradeLevel::Sixieme, 'Frais de scolarité', ['2026-10-15' => 60000, '2027-01-15' => 50000]);

    Payment::factory()->for($this->yao)->create(['amount' => 75000]);
    Payment::factory()->for($this->fatou)->create(['amount' => 5000]);
    $this->travelTo(Carbon::parse('2026-11-02'));
    $this->actingAs($this->staff = User::factory()->create());
});

test('the reminder lists the overdue installments and what is left for the year', function () {
    $message = PaymentReminder::defaultMessage(new StudentAccount($this->fatou), Carbon::parse('2026-11-02'));

    expect($message)
        ->toContain('au 02/11/2026, il reste à régler pour Diallo Fatou (6e1) un retard de 70 000 FCFA')
        ->toContain('- Inscription (échéance du 15/09/2026) : 10 000 FCFA')
        ->toContain('- Frais de scolarité — 1re tranche (échéance du 15/10/2026) : 60 000 FCFA')
        ->not->toContain('2e tranche')
        ->toContain('Reste à payer sur l\'année : 120 000 FCFA');
});

test('staff remind a family on the portal, and the next reminder follows in the same conversation', function () {
    Livewire::test(ArrearsPage::class)
        ->set('criteria.includes_up_to_date', true)
        ->assertActionHidden(TestAction::make('remind')->table($this->yao->id))
        ->mountAction(TestAction::make('remind')->table($this->awa->id))
        ->assertSchemaStateSet(['body' => PaymentReminder::defaultMessage(new StudentAccount($this->awa), now())], 'mountedActionSchema0')
        ->callMountedAction()
        ->assertHasNoActionErrors()
        ->assertSee(now()->format('d/m/Y'));

    $conversation = Conversation::sole();
    expect($conversation)
        ->family_id->toBe($this->awa->student->family_id)
        ->student_id->toBe($this->awa->student_id)
        ->teacher_id->toBeNull()
        ->and($conversation->messages()->sole()->author_id)->toBe($this->staff->id)
        ->and(PaymentReminder::sole())
        ->overdue->toBe(75000)
        ->sent_by->toBe($this->staff->id);

    Livewire::test(ArrearsPage::class)
        ->callAction(TestAction::make('remind')->table($this->awa->id), data: ['body' => 'Deuxième relance'])
        ->assertHasNoActionErrors();

    expect(Conversation::count())->toBe(1)
        ->and($conversation->messages()->latest('id')->first()->body)->toBe('Deuxième relance');
});

test('reminding a selection skips the students reminded lately', function () {
    PaymentReminder::factory()->for($this->fatou)->create(['created_at' => now()->subDays(3)]);

    Livewire::test(ArrearsPage::class)
        ->selectTableRecords([$this->awa->id, $this->fatou->id])
        ->callAction(TestAction::make('remind')->table()->bulk())
        ->assertHasNoActionErrors()
        ->assertNotified('1 relance(s) envoyée(s)');

    expect(PaymentReminder::where('enrollment_id', $this->awa->id)->count())->toBe(1)
        ->and(PaymentReminder::where('enrollment_id', $this->fatou->id)->count())->toBe(1);

    $this->travelTo(now()->addDays(5));

    Livewire::test(ArrearsPage::class)
        ->selectTableRecords([$this->fatou->id])
        ->callAction(TestAction::make('remind')->table()->bulk())
        ->assertNotified('1 relance(s) envoyée(s)');

    expect(PaymentReminder::where('enrollment_id', $this->fatou->id)->count())->toBe(2);
});
