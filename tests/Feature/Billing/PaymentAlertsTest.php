<?php

use App\Enums\Core\GradeLevel;
use App\Filament\Portail\Resources\Conversations\ConversationResource;
use App\Filament\Portail\Widgets\PaymentAlerts;
use App\Models\Billing\Payment;
use App\Models\Billing\PaymentReminder;
use App\Models\Core\Enrollment;
use App\Models\Core\Guardian;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\Core\Student;
use App\Models\User;
use App\Services\Billing\StudentAccount;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $schoolYear = SchoolYear::factory()->current()->create();
    $sixieme = SchoolClass::factory()->for($schoolYear)->create(['level' => GradeLevel::Sixieme, 'section' => '1']);

    $this->awa = Enrollment::factory()->for(Student::factory()->state(['last_name' => 'Koné', 'first_name' => 'Awa']))->for($sixieme)->create();
    $this->yao = Enrollment::factory()->for(Student::factory()->state(['last_name' => 'Yao', 'first_name' => 'Kouassi']))->for($sixieme)->create();

    createFee($schoolYear, null, 'Inscription', ['2026-09-15' => 15000]);
    createFee($schoolYear, GradeLevel::Sixieme, 'Frais de scolarité', ['2026-10-15' => 60000, '2027-01-15' => 50000]);
    Payment::factory()->for($this->yao)->create(['amount' => 75000]);

    $this->awaParent = User::factory()->guardian()->create();
    Guardian::factory()->for($this->awa->student->family)->for($this->awaParent)->create();
    $this->yaoParent = User::factory()->guardian()->create();
    Guardian::factory()->for($this->yao->student->family)->for($this->yaoParent)->create();

    Filament::setCurrentPanel('portail');
    $this->travelTo(Carbon::parse('2026-11-02'));
});

test('a parent is told what is overdue for their child, with a link to the school\'s reminder', function () {
    $reminder = PaymentReminder::send(new StudentAccount($this->awa), User::factory()->create(), now());
    $this->actingAs($this->awaParent);

    Livewire::test(PaymentAlerts::class)
        ->assertSee('Paiements en retard')
        ->assertSee('Koné Awa')
        ->assertSee('75 000 FCFA en retard')
        ->assertSee('échéance du 15/09/2026')
        ->assertSeeHtml('voir le message de l\'école')
        ->assertDontSee('Yao Kouassi');

    $this->get(ConversationResource::getUrl('view', ['record' => $reminder->conversation_id]))->assertOk();
});

test('an installment due soon is announced, and nothing shows for a child up to date', function () {
    $this->actingAs($this->yaoParent);
    expect(PaymentAlerts::canView())->toBeFalse();

    $this->travelTo(Carbon::parse('2027-01-05'));

    expect(PaymentAlerts::canView())->toBeTrue();
    Livewire::test(PaymentAlerts::class)
        ->assertSee('Prochaines échéances')
        ->assertSee('50 000 FCFA à payer avant le 15/01/2027 (Frais de scolarité — 2e tranche)')
        ->assertDontSee('en retard');
});
