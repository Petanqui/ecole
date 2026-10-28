<?php

use App\Enums\Billing\PaymentMethod;
use App\Filament\Gestion\Pages\Billing\CashReportPage;
use App\Models\Billing\Payment;
use App\Models\Core\Enrollment;
use App\Models\User;
use App\Services\Billing\CashReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-28 17:00'));
    $this->awa = User::factory()->create(['name' => 'Awa Caisse']);
    $this->marc = User::factory()->create(['name' => 'Marc Compta']);
    $enrollment = Enrollment::factory()->create();

    $record = fn (User $cashier, int $amount, PaymentMethod $method = PaymentMethod::Cash, string $paidOn = '2026-10-28'): Payment => Payment::factory()
        ->for($enrollment)
        ->create(['received_by' => $cashier->id, 'amount' => $amount, 'method' => $method, 'paid_on' => $paidOn, 'reference' => $method === PaymentMethod::Cash ? null : 'TX1']);

    $this->todayCash = $record($this->awa, 50000);
    $this->todayWave = $record($this->awa, 30000, PaymentMethod::Wave);
    $this->marcCash = $record($this->marc, 20000);
    $this->cancelled = $record($this->marc, 70000);
    $this->cancelled->cancel($this->marc, 'Doublon');
    $this->yesterday = $record($this->awa, 15000, paidOn: '2026-10-27');
});

test('the report counts the payments of the period by method and by cashier, never the cancelled ones', function () {
    $report = new CashReport(Carbon::parse('2026-10-28'), Carbon::parse('2026-10-28'));

    expect($report->total())->toBe(100000)
        ->and($report->cancelledPayments()->modelKeys())->toBe([$this->cancelled->id])
        ->and($report->byMethod()->map(fn (array $row): array => [$row['method'], $row['count'], $row['total']])->all())->toBe([
            [PaymentMethod::Cash, 2, 70000],
            [PaymentMethod::Wave, 1, 30000],
        ])
        ->and($report->byCashier()->all())->toBe([
            ['cashier' => 'Awa Caisse', 'count' => 2, 'cash' => 50000, 'other' => 30000, 'total' => 80000],
            ['cashier' => 'Marc Compta', 'count' => 1, 'cash' => 20000, 'other' => 0, 'total' => 20000],
        ]);

    $awaOverTwoDays = new CashReport(Carbon::parse('2026-10-27'), Carbon::parse('2026-10-28'), receiverId: $this->awa->id);
    expect($awaOverTwoDays->total())->toBe(95000);
});

test('staff see today\'s cash desk, narrow it down and export it', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(CashReportPage::class)
        ->assertSee('100 000 FCFA')
        ->assertSee('1 annulé(s)')
        ->assertCanSeeTableRecords([$this->todayCash, $this->todayWave, $this->marcCash, $this->cancelled])
        ->assertCanNotSeeTableRecords([$this->yesterday])
        ->set('criteria.method', PaymentMethod::Wave->value)
        ->assertCanSeeTableRecords([$this->todayWave])
        ->assertCanNotSeeTableRecords([$this->todayCash])
        ->callAction('export')
        ->assertFileDownloaded('caisse-2026-10-28-2026-10-28.csv');
});

test('staff print the cash report, teachers cannot', function () {
    $url = route('filament.gestion.cash-report', ['du' => '2026-10-27', 'au' => '2026-10-28', 'caissier' => $this->awa->id]);

    $this->actingAs(User::factory()->create())
        ->get($url)
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs(User::factory()->teacher()->create());
    $this->get($url)->assertForbidden();
    $this->get(CashReportPage::getUrl())->assertForbidden();
});
