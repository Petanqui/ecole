<?php

namespace App\Filament\Gestion\Widgets;

use App\Enums\Attendance\AttendanceStatus;
use App\Filament\Gestion\Concerns\VisibleToStaffMembers;
use App\Models\Core\SchoolClass;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Absences of each class this year. The absence rate compares the hours missed with the hours
 * of the roll calls taken, times the number of students.
 */
class AttendanceByClass extends TableWidget
{
    use VisibleToStaffMembers;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Assiduité par classe (année en cours)')
            ->query(fn (): Builder => SchoolClass::query()
                ->whereRelation('schoolYear', 'is_current', true)
                ->withCount('enrollments')
                ->selectSub($this->attendanceHours(), 'absence_hours')
                ->selectSub($this->attendanceHours()->whereNull('attendances.justified_at'), 'unjustified_hours')
                ->selectSub(DB::table('attendances')
                    ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendances.attendance_session_id')
                    ->join('class_subjects', 'class_subjects.id', '=', 'attendance_sessions.class_subject_id')
                    ->whereColumn('class_subjects.school_class_id', 'school_classes.id')
                    ->where('attendances.status', AttendanceStatus::Late->value)
                    ->selectRaw('count(*)'), 'late_arrivals')
                ->selectSub(DB::table('attendance_sessions')
                    ->join('class_subjects', 'class_subjects.id', '=', 'attendance_sessions.class_subject_id')
                    ->whereColumn('class_subjects.school_class_id', 'school_classes.id')
                    ->selectRaw('coalesce(sum(attendance_sessions.hours), 0)'), 'taught_hours'))
            ->defaultSort('absence_hours', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('name')
                    ->label('Classe')
                    ->sortable(),
                TextColumn::make('enrollments_count')
                    ->label('Élèves')
                    ->alignCenter(),
                TextColumn::make('taught_hours')
                    ->label('Heures d\'appel')
                    ->alignCenter(),
                TextColumn::make('absence_hours')
                    ->label('Heures d\'absence')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('unjustified_hours')
                    ->label('Non justifiées')
                    ->alignCenter()
                    ->sortable()
                    ->color(fn (mixed $state): ?string => (int) $state > 0 ? 'danger' : null),
                TextColumn::make('late_arrivals')
                    ->label('Retards')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('absence_rate')
                    ->label('Taux d\'absence')
                    ->alignEnd()
                    ->state(fn (SchoolClass $record): ?float => $record->taught_hours > 0 && $record->enrollments_count > 0
                        ? round(100 * $record->absence_hours / ($record->taught_hours * $record->enrollments_count), 1)
                        : null)
                    ->formatStateUsing(fn (float $state): string => number_format($state, 1, ',', ' ').' %')
                    ->placeholder('—'),
            ]);
    }

    /**
     * Hours of absence of the students of the class (correlated with school_classes.id).
     */
    protected function attendanceHours(): QueryBuilder
    {
        return DB::table('attendances')
            ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendances.attendance_session_id')
            ->join('class_subjects', 'class_subjects.id', '=', 'attendance_sessions.class_subject_id')
            ->whereColumn('class_subjects.school_class_id', 'school_classes.id')
            ->where('attendances.status', AttendanceStatus::Absent->value)
            ->selectRaw('coalesce(sum(attendance_sessions.hours), 0)');
    }
}
