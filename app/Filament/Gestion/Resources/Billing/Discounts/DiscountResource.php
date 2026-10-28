<?php

namespace App\Filament\Gestion\Resources\Billing\Discounts;

use App\Enums\Billing\DiscountKind;
use App\Filament\Gestion\Concerns\RestrictedToStaffMembers;
use App\Filament\Gestion\Resources\Billing\Discounts\Pages\ManageDiscounts;
use App\Models\Billing\Discount;
use App\Models\Billing\Fee;
use App\Models\Core\SchoolYear;
use App\Services\Billing\StudentAccount;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Kinds of reduction of a school year, then given to students on their enrollment.
 */
class DiscountResource extends Resource
{
    use RestrictedToStaffMembers;

    protected static ?string $model = Discount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptRefund;

    protected static string|UnitEnum|null $navigationGroup = 'Facturation';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Réductions';

    protected static ?string $modelLabel = 'réduction';

    protected static ?string $pluralModelLabel = 'réductions';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('school_year_id')
                    ->label('Année scolaire')
                    ->relationship('schoolYear', 'name')
                    ->default(fn (): ?int => SchoolYear::current()->value('id'))
                    ->required()
                    ->live(),
                TextInput::make('name')
                    ->label('Libellé')
                    ->placeholder('Fratrie, Bourse, Enfant du personnel…')
                    ->required()
                    ->maxLength(100),
                ToggleButtons::make('kind')
                    ->label('Type')
                    ->options(DiscountKind::class)
                    ->default(DiscountKind::Percentage)
                    ->inline()
                    ->required()
                    ->live(),
                TextInput::make('value')
                    ->label('Réduction')
                    ->integer()
                    ->minValue(1)
                    ->maxValue(fn (Get $get): ?int => static::kind($get) === DiscountKind::Percentage ? 100 : null)
                    ->suffix(fn (Get $get): string => static::kind($get) === DiscountKind::Percentage ? '%' : 'FCFA')
                    ->helperText(fn (Get $get): ?string => static::kind($get) === DiscountKind::Amount
                        ? 'Partagé entre les frais cochés en proportion de leur montant.'
                        : null)
                    ->required(),
                CheckboxList::make('fees')
                    ->label('S\'applique aux frais')
                    ->relationship('fees', 'name', modifyQueryUsing: fn (Builder $query, Get $get) => $query
                        ->where('school_year_id', $get('school_year_id'))
                        ->with('installments')
                        ->orderBy('name'))
                    ->getOptionLabelFromRecordUsing(fn (Fee $record): string => $record->name
                        .($record->level ? " — {$record->level->getLabel()}" : ' — tous niveaux')
                        .' ('.StudentAccount::formatAmount($record->amount).')')
                    ->required()
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['fees', 'schoolYear'])->withCount('enrollments'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Libellé')
                    ->searchable(),
                TextColumn::make('value_label')
                    ->label('Réduction'),
                TextColumn::make('fees.name')
                    ->label('Sur les frais')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('enrollments_count')
                    ->label('Élèves'),
                TextColumn::make('schoolYear.name')
                    ->label('Année'),
            ])
            ->filters([
                SelectFilter::make('school_year_id')
                    ->label('Année scolaire')
                    ->relationship('schoolYear', 'name')
                    ->default(fn (): ?int => SchoolYear::current()->value('id')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn (Discount $record): bool => $record->enrollments_count > 0)
                    ->tooltip(fn (Discount $record): ?string => $record->enrollments_count > 0 ? 'Des élèves bénéficient de cette réduction.' : null),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDiscounts::route('/'),
        ];
    }

    protected static function kind(Get $get): ?DiscountKind
    {
        $kind = $get('kind');

        return $kind instanceof DiscountKind ? $kind : DiscountKind::tryFrom((string) $kind);
    }
}
