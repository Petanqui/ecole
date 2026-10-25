<?php

namespace App\Filament\Gestion\Resources\Core\SchoolYears;

use App\Filament\Gestion\Resources\Core\SchoolYears\Pages\ManageSchoolYears;
use App\Models\Core\SchoolYear;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SchoolYearResource extends Resource
{
    protected static ?string $model = SchoolYear::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Scolarité';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'année scolaire';

    protected static ?string $pluralModelLabel = 'années scolaires';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom')
                    ->placeholder('2026-2027')
                    ->required()
                    ->maxLength(20)
                    ->unique(),
                Toggle::make('is_current')
                    ->label('Année en cours')
                    ->helperText('Une seule année peut être en cours : les autres seront désactivées.')
                    ->inline(false),
                DatePicker::make('starts_on')
                    ->label('Date de rentrée')
                    ->required(),
                DatePicker::make('ends_on')
                    ->label('Date de fin')
                    ->required()
                    ->after('starts_on'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('classes'))
            ->defaultSort('starts_on', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('starts_on')
                    ->label('Rentrée')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('ends_on')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('classes_count')
                    ->label('Classes'),
                IconColumn::make('is_current')
                    ->label('En cours')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (SchoolYear $record): bool => $record->classes_count > 0),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSchoolYears::route('/'),
        ];
    }
}
