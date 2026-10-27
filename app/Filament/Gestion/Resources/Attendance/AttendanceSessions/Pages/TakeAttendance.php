<?php

namespace App\Filament\Gestion\Resources\Attendance\AttendanceSessions\Pages;

use App\Enums\Attendance\AttendanceStatus;
use App\Filament\Gestion\Resources\Attendance\AttendanceSessions\AttendanceSessionResource;
use App\Models\Attendance\Attendance;
use App\Models\Attendance\AttendanceSession;
use App\Models\Core\Enrollment;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

/**
 * The roll call itself: every student of the class, present unless marked absent or late.
 *
 * @property-read AttendanceSession $record
 */
class TakeAttendance extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = AttendanceSessionResource::class;

    protected string $view = 'filament.gestion.resources.attendance.take-attendance';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(auth()->user()->can('view', $this->record), 403);
    }

    public function getTitle(): string|Htmlable
    {
        $classSubject = $this->getSession()->classSubject;

        return "Appel — {$classSubject->schoolClass->name} — {$classSubject->subject->name}";
    }

    public function getSubheading(): string|Htmlable|null
    {
        $session = $this->getSession();
        $counts = $session->attendances()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return "{$session->held_on->translatedFormat('l j F Y')}, {$session->time_slot} — "
            .($counts[AttendanceStatus::Absent->value] ?? 0).' absent(s), '
            .($counts[AttendanceStatus::Late->value] ?? 0).' retard(s)';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('everyonePresent')
                ->label('Tous présents')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Les absences et retards notés à cet appel seront supprimés.')
                ->visible(fn (): bool => $this->canBeChanged())
                ->action(fn () => $this->getSession()->attendances()->delete()),
            Action::make('back')
                ->label('Retour aux appels')
                ->color('gray')
                ->url(AttendanceSessionResource::getUrl()),
        ];
    }

    public function table(Table $table): Table
    {
        $session = $this->getSession();

        return $table
            ->query(fn (): Builder => Enrollment::query()
                ->where('school_class_id', $session->classSubject->school_class_id)
                ->with([
                    'student',
                    'attendances' => fn ($query) => $query->where('attendance_session_id', $session->getKey()),
                ])
                ->orderBy(fn ($query) => $query->select('last_name')->from('students')->whereColumn('students.id', 'enrollments.student_id'))
                ->orderBy(fn ($query) => $query->select('first_name')->from('students')->whereColumn('students.id', 'enrollments.student_id')))
            ->paginated(false)
            ->emptyStateHeading('Aucun élève inscrit dans cette classe')
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('Élève'),
                TextColumn::make('student.code')
                    ->label('Code')
                    ->color('gray'),
                SelectColumn::make('status')
                    ->label('Présence')
                    ->options(AttendanceStatus::class)
                    ->selectablePlaceholder(false)
                    ->disabled(fn (): bool => ! $this->canBeChanged())
                    ->getStateUsing(fn (Enrollment $record): string => $this->attendanceOf($record)?->status->value ?? AttendanceStatus::Present->value)
                    ->updateStateUsing(fn (Enrollment $record, string $state) => $session->mark($record, AttendanceStatus::from($state))),
                TextColumn::make('justification')
                    ->label('Justification')
                    ->state(fn (Enrollment $record): ?string => $this->attendanceOf($record)?->isJustified()
                        ? ($this->attendanceOf($record)->justification ?: 'Justifié(e)')
                        : null)
                    ->color('success')
                    ->placeholder('—'),
            ]);
    }

    protected function getSession(): AttendanceSession
    {
        /** @var AttendanceSession */
        return $this->getRecord();
    }

    protected function attendanceOf(Enrollment $enrollment): ?Attendance
    {
        return $enrollment->attendances->first();
    }

    protected function canBeChanged(): bool
    {
        return auth()->user()->can('update', $this->getSession());
    }
}
