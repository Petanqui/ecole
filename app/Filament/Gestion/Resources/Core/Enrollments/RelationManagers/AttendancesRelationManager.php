<?php

namespace App\Filament\Gestion\Resources\Core\Enrollments\RelationManagers;

use App\Filament\Gestion\Resources\Attendance\Attendances\Tables\AttendancesTable;
use App\Models\Core\Enrollment;
use App\Models\Grades\Term;
use App\Services\Attendance\AttendanceSummary;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

/**
 * The absences and late arrivals of a student for the year, with the totals of each term.
 */
class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';

    protected static ?string $title = 'Absences';

    protected static ?string $modelLabel = 'absence';

    protected static ?string $pluralModelLabel = 'absences et retards';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return AttendancesTable::configure($table, showsStudent: false)
            ->heading('Absences et retards')
            ->description(fn (): string => $this->summary())
            ->emptyStateHeading('Aucune absence ni aucun retard');
    }

    protected function summary(): string
    {
        /** @var Enrollment $enrollment */
        $enrollment = $this->getOwnerRecord();

        return $enrollment->schoolYear->terms
            ->map(fn (Term $term): string => "{$term->name} : ".AttendanceSummary::forEnrollment($enrollment, $term)->describe())
            ->push('Année : '.AttendanceSummary::forEnrollment($enrollment)->describe())
            ->join(' · ');
    }
}
