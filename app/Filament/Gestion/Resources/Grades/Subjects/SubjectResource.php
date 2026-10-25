<?php

namespace App\Filament\Gestion\Resources\Grades\Subjects;

use App\Filament\Gestion\Concerns\RestrictedToStaffMembers;
use App\Filament\Gestion\Resources\Grades\Subjects\Pages\ManageSubjects;
use App\Models\Grades\Subject;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SubjectResource extends Resource
{
    use RestrictedToStaffMembers;

    protected static ?string $model = Subject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Notes';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'matière';

    protected static ?string $pluralModelLabel = 'matières';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->label('Abréviation')
                    ->helperText('Utilisée dans les programmes types et sur le bulletin, ex. MATH.')
                    ->required()
                    ->maxLength(10)
                    ->mutateStateForValidationUsing(fn (?string $state): ?string => $state === null ? null : strtoupper(preg_replace('/\s+/', '', $state)))
                    ->unique(ignoreRecord: true),
                TextInput::make('sort_order')
                    ->label('Ordre sur le bulletin')
                    ->integer()
                    ->minValue(0)
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('classSubjects'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('code')
                    ->label('Abréviation')
                    ->badge()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('class_subjects_count')
                    ->label('Classes'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Subject $record): bool => $record->class_subjects_count > 0),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSubjects::route('/'),
        ];
    }
}
