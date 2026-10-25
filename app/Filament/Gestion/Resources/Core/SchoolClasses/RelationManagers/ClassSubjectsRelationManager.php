<?php

namespace App\Filament\Gestion\Resources\Core\SchoolClasses\RelationManagers;

use App\Enums\Core\UserRole;
use App\Models\Core\SchoolClass;
use App\Models\Grades\Subject;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

/**
 * The programme of a class: subjects, coefficients and teachers.
 */
class ClassSubjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'classSubjects';

    protected static ?string $title = 'Programme';

    protected static ?string $modelLabel = 'matière';

    protected static ?string $pluralModelLabel = 'matières';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('subject_id')
                    ->label('Matière')
                    ->relationship('subject', 'name', fn (Builder $query) => $query->orderBy('sort_order'))
                    ->required()
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('school_class_id', $this->getOwnerRecord()->getKey()))
                    ->validationMessages(['unique' => 'Cette matière est déjà au programme de la classe.']),
                TextInput::make('coefficient')
                    ->label('Coefficient')
                    ->integer()
                    ->minValue(1)
                    ->maxValue(10)
                    ->default(1)
                    ->required(),
                Select::make('teacher_id')
                    ->label('Enseignant')
                    ->relationship('teacher', 'name', fn (Builder $query) => $query->where('role', UserRole::Teacher)->orderBy('name'))
                    ->searchable()
                    ->preload(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['subject', 'teacher']))
            ->defaultSort(fn (Builder $query) => $query->orderBy(
                Subject::select('sort_order')->whereColumn('subjects.id', 'class_subjects.subject_id'),
            ))
            ->paginated(false)
            ->columns([
                TextColumn::make('subject.name')
                    ->label('Matière'),
                TextColumn::make('coefficient')
                    ->label('Coef.'),
                TextColumn::make('teacher.name')
                    ->label('Enseignant')
                    ->placeholder('Non attribué'),
            ])
            ->headerActions([
                Action::make('applyDefaultProgramme')
                    ->label('Compléter avec le programme type')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('Ajoute les matières du programme type de ce niveau qui manquent. Les matières déjà présentes gardent leur coefficient et leur enseignant.')
                    ->action(function (): void {
                        /** @var SchoolClass $schoolClass */
                        $schoolClass = $this->getOwnerRecord();
                        $added = $schoolClass->applyDefaultProgramme();

                        Notification::make()
                            ->success()
                            ->title($added > 0 ? "{$added} matière(s) ajoutée(s)" : 'Le programme était déjà complet')
                            ->send();
                    }),
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
