<?php

namespace App\Filament\Gestion\Resources\Core\Enrollments\Schemas;

use App\Models\Core\Enrollment;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\Core\Student;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class EnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('student_id')
                    ->label('Élève')
                    ->relationship('student', 'last_name')
                    ->getOptionLabelFromRecordUsing(fn (Student $record): string => "{$record->full_name} ({$record->code})")
                    ->searchable(['last_name', 'first_name', 'code', 'mena_number'])
                    ->required()
                    ->hidden(fn (Component $livewire): bool => $livewire instanceof RelationManager),
                Select::make('school_class_id')
                    ->label('Classe')
                    ->relationship(
                        'schoolClass',
                        'name',
                        modifyQueryUsing: fn (Builder $query) => $query->with('schoolYear')->orderByDesc('school_year_id')->orderBy('name'),
                    )
                    ->getOptionLabelFromRecordUsing(fn (SchoolClass $record): string => "{$record->name} ({$record->schoolYear->name})")
                    ->searchable()
                    ->preload()
                    ->required()
                    ->rules([
                        fn (?Enrollment $record): Closure => static::classIsNotFull($record),
                        fn (Get $get, ?Enrollment $record, Component $livewire): Closure => static::studentIsNotAlreadyEnrolled(
                            $livewire instanceof RelationManager ? $livewire->getOwnerRecord()->getKey() : $get('student_id'),
                            $record,
                        ),
                    ]),
                DatePicker::make('enrolled_on')
                    ->label('Date d\'inscription')
                    ->default(now())
                    ->required(),
            ]);
    }

    /**
     * Refuse a class that has reached its capacity, unless the enrollment is already in that class.
     */
    protected static function classIsNotFull(?Enrollment $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($record): void {
            if ($record?->school_class_id === (int) $value) {
                return;
            }

            if (SchoolClass::find($value)?->isFull()) {
                $fail('Cette classe est complète.');
            }
        };
    }

    /**
     * Refuse a second enrollment of the same student for the school year of the chosen class.
     */
    protected static function studentIsNotAlreadyEnrolled(mixed $studentId, ?Enrollment $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($studentId, $record): void {
            $schoolYearId = SchoolClass::whereKey($value)->value('school_year_id');

            $alreadyEnrolled = Enrollment::query()
                ->where('student_id', $studentId)
                ->where('school_year_id', $schoolYearId)
                ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                ->exists();

            if ($alreadyEnrolled) {
                $fail('Cet élève est déjà inscrit pour l\'année '.SchoolYear::whereKey($schoolYearId)->value('name').'.');
            }
        };
    }
}
