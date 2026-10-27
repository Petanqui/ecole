<?php

namespace App\Filament\Gestion\Resources\Billing\Fees\Tables;

use App\Enums\Core\GradeLevel;
use App\Models\Billing\Fee;
use App\Models\Billing\FeeInstallment;
use App\Models\Core\SchoolYear;
use App\Services\Billing\StudentAccount;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['installments'])->withCount('enrollments'))
            ->defaultSort(fn (Builder $query) => $query->orderBy('is_optional')->orderBy('level')->orderBy('name'))
            ->columns([
                TextColumn::make('name')
                    ->label('Libellé')
                    ->searchable(),
                TextColumn::make('level')
                    ->label('Niveau')
                    ->placeholder('Tous les niveaux'),
                TextColumn::make('is_optional')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Optionnel' : 'Obligatoire')
                    ->color(fn (bool $state): string => $state ? 'gray' : 'primary'),
                TextColumn::make('amount')
                    ->label('Montant')
                    ->state(fn (Fee $record): string => StudentAccount::formatAmount($record->amount)),
                TextColumn::make('installments')
                    ->label('Échéances')
                    ->state(fn (Fee $record): array => $record->installments
                        ->map(fn (FeeInstallment $installment): string => $installment->due_on->format('d/m/Y').' : '.StudentAccount::formatAmount($installment->amount))
                        ->all())
                    ->listWithLineBreaks(),
                TextColumn::make('enrollments_count')
                    ->label('Élèves inscrits')
                    ->formatStateUsing(fn (int $state, Fee $record): string => $record->is_optional ? (string) $state : '—'),
            ])
            ->filters([
                SelectFilter::make('school_year_id')
                    ->label('Année scolaire')
                    ->relationship('schoolYear', 'name')
                    ->default(fn (): ?int => SchoolYear::current()->value('id')),
                SelectFilter::make('level')
                    ->label('Niveau')
                    ->options(GradeLevel::groupedOptions()),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()
                    ->label('Copier')
                    ->excludeAttributes(['enrollments_count'])
                    ->modalHeading('Copier ce frais vers un autre niveau')
                    ->modalDescription('Le libellé et l\'échéancier sont repris ; vous pourrez ajuster les montants ensuite.')
                    ->schema([
                        Select::make('level')
                            ->label('Niveau')
                            ->options(GradeLevel::groupedOptions())
                            ->placeholder('Tous les niveaux'),
                    ])
                    ->after(function (Fee $record, Fee $replica): void {
                        $replica->installments()->createMany(
                            $record->installments->map->only(['due_on', 'amount'])->all(),
                        );
                    })
                    ->successNotificationTitle('Frais copié'),
                DeleteAction::make()
                    ->disabled(fn (Fee $record): bool => $record->enrollments_count > 0)
                    ->tooltip(fn (Fee $record): ?string => $record->enrollments_count > 0 ? 'Des élèves sont inscrits à ce frais.' : null),
            ]);
    }
}
