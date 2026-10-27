<?php

namespace App\Filament\Gestion\Resources\Attendance\AttendanceSessions\Schemas;

use App\Models\Attendance\AttendanceSession;
use App\Models\Grades\ClassSubject;
use App\Models\Grades\Term;
use App\Models\User;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AttendanceSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('class_subject_id')
                    ->label('Classe et matière')
                    ->options(fn (): array => static::classSubjectOptions())
                    ->searchable()
                    ->required()
                    ->live()
                    ->disabledOn('edit')
                    ->afterStateUpdated(fn (Get $get, Set $set) => $set('term_id', array_key_first(static::termOptions($get('class_subject_id')))))
                    ->columnSpanFull(),
                Select::make('term_id')
                    ->label('Trimestre')
                    ->options(fn (Get $get, ?AttendanceSession $record): array => static::termOptions($get('class_subject_id') ?? $record?->class_subject_id))
                    ->required()
                    ->disabledOn('edit'),
                DatePicker::make('held_on')
                    ->label('Date')
                    ->default(now())
                    ->maxDate(now())
                    ->required(),
                TimePicker::make('starts_at')
                    ->label('Début du cours')
                    ->seconds(false)
                    ->default(now()->format('H:00'))
                    ->required()
                    ->rules([
                        fn (Get $get, ?AttendanceSession $record): Closure => static::doesNotOverlapAnother($get, $record),
                    ]),
                TextInput::make('hours')
                    ->label('Durée')
                    ->integer()
                    ->minValue(1)
                    ->maxValue(8)
                    ->default(1)
                    ->suffix('heure(s)')
                    ->required(),
            ]);
    }

    /**
     * @return array<int, string>
     */
    protected static function classSubjectOptions(): array
    {
        /** @var User $user */
        $user = auth()->user();

        return ClassSubject::currentYearOptionsFor($user);
    }

    /**
     * @return array<int, string>
     */
    protected static function termOptions(mixed $classSubjectId): array
    {
        $schoolYearId = ClassSubject::find($classSubjectId)?->schoolClass->school_year_id;

        return $schoolYearId === null ? [] : Term::openOptions($schoolYearId);
    }

    /**
     * A class cannot have two lessons at the same time.
     */
    protected static function doesNotOverlapAnother(Get $get, ?AttendanceSession $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
            $classSubject = ClassSubject::find($get('class_subject_id') ?? $record?->class_subject_id);

            if ($classSubject === null || blank($get('held_on')) || (int) $get('hours') < 1) {
                return;
            }

            if (AttendanceSession::overlapsAnother($classSubject->school_class_id, $get('held_on'), (string) $value, (int) $get('hours'), $record?->getKey())) {
                $fail("La classe {$classSubject->schoolClass->name} a déjà un appel sur ce créneau.");
            }
        };
    }
}
