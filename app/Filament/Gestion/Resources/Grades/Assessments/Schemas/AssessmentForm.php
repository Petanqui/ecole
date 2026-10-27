<?php

namespace App\Filament\Gestion\Resources\Grades\Assessments\Schemas;

use App\Enums\Grades\AssessmentType;
use App\Models\Grades\Assessment;
use App\Models\Grades\ClassSubject;
use App\Models\Grades\Term;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AssessmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('class_subject_id')
                    ->label('Classe et matière')
                    ->options(fn (): array => static::classSubjectOptions())
                    ->searchable()
                    ->required()
                    ->live()
                    ->disabledOn('edit')
                    ->afterStateUpdated(function (Get $get, Set $set): void {
                        $set('term_id', array_key_first(static::termOptions($get('class_subject_id'))));
                        static::fillDefaultMaxScore($get, $set);
                    }),
                Select::make('term_id')
                    ->label('Trimestre')
                    ->options(fn (Get $get): array => static::termOptions($get('class_subject_id')))
                    ->required()
                    ->disabledOn('edit'),
                ToggleButtons::make('type')
                    ->label('Type')
                    ->options(AssessmentType::class)
                    ->default(AssessmentType::Homework)
                    ->inline()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => static::fillDefaultMaxScore($get, $set)),
                TextInput::make('max_score')
                    ->label('Noté sur')
                    ->integer()
                    ->minValue(1)
                    ->maxValue(100)
                    ->default(20)
                    ->required()
                    ->disabled(fn (?Assessment $record): bool => static::hasScores($record))
                    ->helperText(fn (?Assessment $record): ?string => static::hasScores($record)
                        ? 'Des notes sont déjà saisies : le barème ne peut plus changer.'
                        : null),
                DatePicker::make('held_on')
                    ->label('Date')
                    ->default(now())
                    ->required(),
                TextInput::make('title')
                    ->label('Intitulé')
                    ->placeholder('Facultatif, ex. « Devoir n° 2 – fractions »')
                    ->maxLength(255),
            ]);
    }

    /**
     * Subjects of current-year classes, limited to their own subjects for a teacher.
     *
     * @return array<int, string>
     */
    protected static function classSubjectOptions(): array
    {
        /** @var User $user */
        $user = auth()->user();

        return ClassSubject::currentYearOptionsFor($user);
    }

    /**
     * Open terms of the school year of the chosen class.
     *
     * @return array<int, string>
     */
    protected static function termOptions(mixed $classSubjectId): array
    {
        $schoolYearId = ClassSubject::find($classSubjectId)?->schoolClass->school_year_id;

        if ($schoolYearId === null) {
            return [];
        }

        return Term::openOptions($schoolYearId);
    }

    /**
     * Once scores are entered, changing the maximum score would silently change their meaning.
     */
    protected static function hasScores(?Assessment $assessment): bool
    {
        return $assessment?->marks()->whereNotNull('score')->exists() ?? false;
    }

    protected static function fillDefaultMaxScore(Get $get, Set $set): void
    {
        $level = ClassSubject::find($get('class_subject_id'))?->schoolClass->level;
        $type = $get('type') instanceof AssessmentType ? $get('type') : AssessmentType::tryFrom((string) $get('type'));

        if ($level && $type) {
            $set('max_score', $type->defaultMaxScore($level));
        }
    }
}
