<?php

namespace App\Filament\Gestion\Pages\Billing;

use App\Enums\Billing\PaymentMethod;
use App\Filament\Gestion\Concerns\RestrictedToStaffMembers;
use App\Filament\Gestion\Resources\Billing\Payments\Tables\PaymentsTable;
use App\Models\Billing\Payment;
use App\Services\Billing\CashReport;
use App\Services\Billing\StudentAccount;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * What was collected over a period, by payment method and by cashier, to close the cash desk.
 *
 * @property-read Schema $form
 */
class CashReportPage extends Page implements HasTable
{
    use InteractsWithTable;
    use RestrictedToStaffMembers;

    protected string $view = 'filament.gestion.pages.billing.cash-report-page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string|UnitEnum|null $navigationGroup = 'Facturation';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Caisse';

    protected static ?string $title = 'Caisse';

    protected static ?string $slug = 'caisse';

    /**
     * @var array{from?: string|null, until?: string|null, received_by?: int|string|null, method?: string|null}
     */
    public array $criteria = [];

    protected ?CashReport $report = null;

    public function mount(): void
    {
        $this->form->fill(['from' => now()->toDateString(), 'until' => now()->toDateString()]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('criteria')
            ->components([
                Grid::make(4)->schema([
                    DatePicker::make('from')
                        ->label('Du')
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn () => $this->refreshReport()),
                    DatePicker::make('until')
                        ->label('Au')
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn () => $this->refreshReport()),
                    Select::make('received_by')
                        ->label('Caissier')
                        ->options(fn (): array => CashReport::cashierOptions())
                        ->placeholder('Tous')
                        ->live()
                        ->afterStateUpdated(fn () => $this->refreshReport()),
                    Select::make('method')
                        ->label('Mode de paiement')
                        ->options(PaymentMethod::class)
                        ->placeholder('Tous')
                        ->live()
                        ->afterStateUpdated(fn () => $this->refreshReport()),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->report()->query()->with(['enrollment.student', 'enrollment.schoolClass', 'receiver']))
            ->defaultSort(fn ($query) => $query->orderBy('paid_on')->orderBy('id'))
            ->heading('Paiements')
            ->emptyStateHeading('Aucun paiement')
            ->emptyStateDescription('Aucun paiement n\'a été enregistré sur cette période.')
            ->columns([
                TextColumn::make('receipt_number')
                    ->label('Reçu'),
                TextColumn::make('paid_on')
                    ->label('Date')
                    ->date('d/m/Y'),
                TextColumn::make('enrollment.student.full_name')
                    ->label('Élève'),
                TextColumn::make('enrollment.schoolClass.name')
                    ->label('Classe'),
                TextColumn::make('method')
                    ->label('Mode')
                    ->badge()
                    ->description(fn (Payment $record): ?string => $record->reference),
                TextColumn::make('receiver.name')
                    ->label('Reçu par')
                    ->placeholder('—'),
                TextColumn::make('amount')
                    ->label('Montant')
                    ->formatStateUsing(fn (int $state): string => StudentAccount::formatAmount($state))
                    ->color(fn (Payment $record): ?string => $record->isCancelled() ? 'gray' : null),
                TextColumn::make('cancelled_at')
                    ->label('Statut')
                    ->badge()
                    ->state(fn (Payment $record): string => $record->isCancelled() ? 'Annulé' : 'Valide')
                    ->color(fn (string $state): string => $state === 'Annulé' ? 'danger' : 'success')
                    ->tooltip(fn (Payment $record): ?string => $record->cancellation_reason),
            ])
            ->recordActions(PaymentsTable::recordActions());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Imprimer l\'état de caisse')
                ->icon(Heroicon::OutlinedPrinter)
                ->url(fn (): string => route('filament.gestion.cash-report', array_filter([
                    'du' => $this->report()->from->toDateString(),
                    'au' => $this->report()->until->toDateString(),
                    'caissier' => $this->report()->receiverId,
                    'mode' => $this->report()->method?->value,
                ])))
                ->openUrlInNewTab(),
            Action::make('export')
                ->label('Exporter (CSV)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (): StreamedResponse => $this->exportCsv()),
        ];
    }

    public function report(): CashReport
    {
        if ($this->report !== null) {
            return $this->report;
        }

        $from = Carbon::parse($this->criteria['from'] ?? now());
        $until = Carbon::parse($this->criteria['until'] ?? $from);

        return $this->report = new CashReport(
            from: $from,
            until: $until->max($from),
            receiverId: filled($this->criteria['received_by'] ?? null) ? (int) $this->criteria['received_by'] : null,
            method: PaymentMethod::tryFrom((string) ($this->criteria['method'] ?? '')),
        );
    }

    protected function refreshReport(): void
    {
        $this->report = null;
        $this->resetTable();
    }

    protected function exportCsv(): StreamedResponse
    {
        $report = $this->report();

        return response()->streamDownload(function () use ($report): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\u{FEFF}");
            fputcsv($output, ['Reçu', 'Date', 'Élève', 'Code', 'Classe', 'Mode', 'Référence', 'Versé par', 'Reçu par', 'Montant', 'Statut'], ';');

            foreach ($report->payments as $payment) {
                fputcsv($output, [
                    $payment->receipt_number,
                    $payment->paid_on->format('d/m/Y'),
                    $payment->enrollment->student->full_name,
                    $payment->enrollment->student->code,
                    $payment->enrollment->schoolClass->name,
                    $payment->method->getLabel(),
                    $payment->reference,
                    $payment->payer_name,
                    $payment->receiver?->name,
                    $payment->amount,
                    $payment->isCancelled() ? 'Annulé' : 'Valide',
                ], ';');
            }

            fclose($output);
        }, 'caisse-'.$report->from->format('Y-m-d').'-'.$report->until->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
