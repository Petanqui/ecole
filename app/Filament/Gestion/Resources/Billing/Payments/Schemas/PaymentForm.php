<?php

namespace App\Filament\Gestion\Resources\Billing\Payments\Schemas;

use App\Enums\Billing\PaymentMethod;
use App\Models\Core\Enrollment;
use App\Models\Core\Student;
use App\Services\Billing\StudentAccount;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Livewire\Component;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('enrollment_id')
                    ->label('Élève')
                    ->searchable()
                    ->options(fn (): array => static::enrollmentOptions(''))
                    ->getSearchResultsUsing(fn (string $search): array => static::enrollmentOptions($search))
                    ->getOptionLabelUsing(fn (mixed $value): ?string => static::enrollmentLabel(Enrollment::find($value)))
                    ->required()
                    ->live()
                    ->helperText(fn (Get $get): ?string => static::balanceSummary(Enrollment::find($get('enrollment_id'))))
                    ->hidden(fn (Component $livewire): bool => $livewire instanceof RelationManager)
                    ->columnSpanFull(),
                TextInput::make('amount')
                    ->label('Montant')
                    ->integer()
                    ->minValue(1)
                    ->suffix('FCFA')
                    ->required()
                    ->helperText(fn (Get $get, Component $livewire): ?string => $livewire instanceof RelationManager
                        ? static::balanceSummary($livewire->getOwnerRecord())
                        : null)
                    ->rules([
                        fn (Get $get, Component $livewire): Closure => static::doesNotExceedBalance(
                            $livewire instanceof RelationManager ? $livewire->getOwnerRecord() : Enrollment::find($get('enrollment_id')),
                        ),
                    ]),
                DatePicker::make('paid_on')
                    ->label('Date du paiement')
                    ->default(now())
                    ->maxDate(now())
                    ->required(),
                ToggleButtons::make('method')
                    ->label('Mode de paiement')
                    ->options(PaymentMethod::class)
                    ->default(PaymentMethod::Cash)
                    ->inline()
                    ->required()
                    ->live()
                    ->columnSpanFull(),
                TextInput::make('reference')
                    ->label(fn (Get $get): string => static::method($get)?->referenceLabel() ?? 'Référence')
                    ->visible(fn (Get $get): bool => static::method($get)?->requiresReference() ?? false)
                    ->required(fn (Get $get): bool => static::method($get)?->requiresReference() ?? false)
                    ->maxLength(100),
                TextInput::make('payer_name')
                    ->label('Versé par')
                    ->placeholder('Facultatif, ex. « M. Koné (père) »')
                    ->maxLength(255),
            ]);
    }

    protected static function method(Get $get): ?PaymentMethod
    {
        $method = $get('method');

        return $method instanceof PaymentMethod ? $method : PaymentMethod::tryFrom((string) $method);
    }

    /**
     * Current-year enrollments whose student matches the search; every enrollment when the search is empty.
     *
     * @return array<int, string>
     */
    protected static function enrollmentOptions(string $search): array
    {
        return Enrollment::query()
            ->whereRelation('schoolYear', 'is_current', true)
            ->matchingStudent($search)
            ->with(['student', 'schoolClass'])
            ->orderBy(Student::select('last_name')->whereColumn('students.id', 'enrollments.student_id'))
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Enrollment $enrollment): array => [$enrollment->getKey() => static::enrollmentLabel($enrollment)])
            ->all();
    }

    protected static function enrollmentLabel(?Enrollment $enrollment): ?string
    {
        if ($enrollment === null) {
            return null;
        }

        return "{$enrollment->student->full_name} ({$enrollment->student->code}) — {$enrollment->schoolClass->name}";
    }

    protected static function balanceSummary(?Enrollment $enrollment): ?string
    {
        if ($enrollment === null) {
            return null;
        }

        $account = new StudentAccount($enrollment);

        return 'Reste à payer : '.StudentAccount::formatAmount($account->balance())
            .' (dont en retard : '.StudentAccount::formatAmount($account->overdue(now())).')';
    }

    /**
     * Refuse a payment larger than what is left to pay for the year.
     */
    protected static function doesNotExceedBalance(?Enrollment $enrollment): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($enrollment): void {
            if ($enrollment === null) {
                return;
            }

            $balance = (new StudentAccount($enrollment))->balance();

            if ((int) $value > $balance) {
                $fail('Le montant dépasse le reste à payer ('.StudentAccount::formatAmount($balance).').');
            }
        };
    }
}
