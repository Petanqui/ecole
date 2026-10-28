<?php

namespace App\Filament\Gestion\Resources\Timetable\Rooms;

use App\Filament\Gestion\Concerns\RestrictedToStaffMembers;
use App\Filament\Gestion\Resources\Timetable\Rooms\Pages\ManageRooms;
use App\Models\Timetable\Room;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class RoomResource extends Resource
{
    use RestrictedToStaffMembers;

    protected static ?string $model = Room::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static string|UnitEnum|null $navigationGroup = 'Emploi du temps';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Salles';

    protected static ?string $modelLabel = 'salle';

    protected static ?string $pluralModelLabel = 'salles';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom')
                    ->placeholder('Ex. « Salle 12 », « Labo SVT », « Salle info »')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100),
                TextInput::make('capacity')
                    ->label('Places')
                    ->integer()
                    ->minValue(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('timetableSlots'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('capacity')
                    ->label('Places')
                    ->placeholder('—'),
                TextColumn::make('timetable_slots_count')
                    ->label('Cours par semaine'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn (Room $record): bool => $record->timetable_slots_count > 0)
                    ->tooltip(fn (Room $record): ?string => $record->timetable_slots_count > 0 ? 'Des cours ont lieu dans cette salle.' : null),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRooms::route('/'),
        ];
    }
}
