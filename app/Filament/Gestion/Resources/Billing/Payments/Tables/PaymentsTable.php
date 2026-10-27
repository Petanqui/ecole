<?php

namespace App\Filament\Gestion\Resources\Billing\Payments\Tables;

use App\Enums\Billing\PaymentMethod;
use App\Models\Billing\Payment;
use App\Models\Core\SchoolYear;
use App\Models\User;
use App\Services\Billing\StudentAccount;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentsTable
{
    public static function configure(Table $table, bool $showsStudent = true): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['enrollment.student', 'enrollment.schoolClass', 'receiver']))
            ->defaultSort(fn (Builder $query) => $query->orderByDesc('paid_on')->orderByDesc('id'))
            ->columns([
                TextColumn::make('receipt_number')
                    ->label('Reçu')
                    ->searchable(),
                TextColumn::make('paid_on')
                    ->label('Date')
                    ->date('d/m/Y'),
                TextColumn::make('enrollment.student.full_name')
                    ->label('Élève')
                    ->visible($showsStudent),
                TextColumn::make('enrollment.schoolClass.name')
                    ->label('Classe')
                    ->visible($showsStudent),
                TextColumn::make('amount')
                    ->label('Montant')
                    ->formatStateUsing(fn (int $state): string => StudentAccount::formatAmount($state))
                    ->color(fn (Payment $record): ?string => $record->isCancelled() ? 'gray' : null)
                    ->summarize(Sum::make()
                        ->label('Total encaissé')
                        ->query(fn ($query) => $query->whereNull('cancelled_at'))
                        ->formatStateUsing(fn (?int $state): string => StudentAccount::formatAmount((int) $state))),
                TextColumn::make('method')
                    ->label('Mode')
                    ->badge()
                    ->description(fn (Payment $record): ?string => $record->reference),
                TextColumn::make('receiver.name')
                    ->label('Reçu par')
                    ->placeholder('—'),
                TextColumn::make('cancelled_at')
                    ->label('Statut')
                    ->badge()
                    ->state(fn (Payment $record): string => $record->isCancelled() ? 'Annulé' : 'Valide')
                    ->color(fn (string $state): string => $state === 'Annulé' ? 'danger' : 'success')
                    ->tooltip(fn (Payment $record): ?string => $record->cancellation_reason),
            ])
            ->filters([
                SelectFilter::make('school_year')
                    ->label('Année scolaire')
                    ->options(fn (): array => SchoolYear::query()->orderByDesc('starts_on')->pluck('name', 'id')->all())
                    ->default(fn (): ?int => SchoolYear::current()->value('id'))
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn (Builder $query, $schoolYearId) => $query->whereRelation('enrollment', 'school_year_id', $schoolYearId),
                    ))
                    ->visible($showsStudent),
                SelectFilter::make('method')
                    ->label('Mode')
                    ->options(PaymentMethod::class),
                Filter::make('paid_on')
                    ->schema([
                        DatePicker::make('from')->label('Du'),
                        DatePicker::make('until')->label('Au'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'], fn (Builder $query, $date) => $query->whereDate('paid_on', '>=', $date))
                        ->when($data['until'], fn (Builder $query, $date) => $query->whereDate('paid_on', '<=', $date))),
                TernaryFilter::make('cancelled')
                    ->label('Paiements annulés')
                    ->placeholder('Tous')
                    ->trueLabel('Seulement les annulés')
                    ->falseLabel('Sans les annulés')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('cancelled_at'),
                        false: fn (Builder $query) => $query->whereNull('cancelled_at'),
                    ),
            ])
            ->recordActions(static::recordActions());
    }

    /**
     * @return array<Action>
     */
    public static function recordActions(): array
    {
        return [
            Action::make('receipt')
                ->label('Reçu')
                ->icon(Heroicon::OutlinedPrinter)
                ->url(fn (Payment $record): string => route('filament.gestion.receipts.show', $record))
                ->openUrlInNewTab(),
            Action::make('cancel')
                ->label('Annuler')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->visible(fn (Payment $record): bool => auth()->user()->can('cancel-payment', $record))
                ->modalHeading(fn (Payment $record): string => "Annuler le paiement {$record->receipt_number}")
                ->modalDescription('Le paiement reste dans l\'historique, marqué « annulé », et ne compte plus dans le compte de l\'élève.')
                ->schema([
                    TextInput::make('reason')
                        ->label('Motif')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (Payment $record, array $data): void {
                    /** @var User $user */
                    $user = auth()->user();
                    $record->cancel($user, $data['reason']);

                    Notification::make()->success()->title("Paiement {$record->receipt_number} annulé")->send();
                }),
        ];
    }
}
