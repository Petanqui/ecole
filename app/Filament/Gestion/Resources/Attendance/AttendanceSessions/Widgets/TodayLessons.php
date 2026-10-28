<?php

namespace App\Filament\Gestion\Resources\Attendance\AttendanceSessions\Widgets;

use App\Enums\Timetable\Weekday;
use App\Filament\Gestion\Resources\Attendance\AttendanceSessions\AttendanceSessionResource;
use App\Models\Attendance\AttendanceSession;
use App\Models\Core\SchoolClass;
use App\Models\Grades\Term;
use App\Models\Timetable\TimetableSlot;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Today's lessons from the timetable: a teacher's own, every lesson for staff, with the roll call
 * to take or already taken.
 */
class TodayLessons extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    /**
     * Today's roll calls, keyed by "class subject id|start minute".
     *
     * @var Collection<string, AttendanceSession>|null
     */
    protected ?Collection $todaySessions = null;

    public function table(Table $table): Table
    {
        $user = static::user();
        $weekday = Weekday::of(today());

        return $table
            ->heading($user->isTeacher() ? 'Mes cours du jour' : 'Cours du jour')
            ->description(ucfirst(today()->translatedFormat('l j F Y')))
            ->query(fn (): Builder => TimetableSlot::query()
                ->where('weekday', $weekday?->value ?? 0)
                ->whereRelation('classSubject.schoolClass.schoolYear', 'is_current', true)
                ->when($user->isTeacher(), fn (Builder $query) => $query->whereRelation('classSubject', 'teacher_id', $user->getKey()))
                ->with(['classSubject.schoolClass', 'classSubject.subject', 'classSubject.teacher', 'room']))
            ->defaultSort('starts_at')
            ->paginated(false)
            ->emptyStateHeading('Aucun cours aujourd\'hui')
            ->emptyStateDescription('Les cours viennent de l\'emploi du temps des classes.')
            ->columns([
                TextColumn::make('time_slot')
                    ->label('Horaire'),
                TextColumn::make('classSubject.schoolClass.name')
                    ->label('Classe'),
                TextColumn::make('classSubject.subject.name')
                    ->label('Matière'),
                TextColumn::make('classSubject.teacher.name')
                    ->label('Enseignant')
                    ->placeholder('Non attribué')
                    ->visible($user->isStaffMember()),
                TextColumn::make('room.name')
                    ->label('Salle')
                    ->placeholder('—'),
                TextColumn::make('roll_call')
                    ->label('Appel')
                    ->badge()
                    ->state(fn (TimetableSlot $record): string => $this->sessionOf($record) ? 'Fait' : 'À faire')
                    ->color(fn (string $state): string => $state === 'Fait' ? 'success' : 'warning'),
            ])
            ->filters([
                SelectFilter::make('school_class')
                    ->label('Classe')
                    ->options(fn (): array => SchoolClass::query()->whereRelation('schoolYear', 'is_current', true)->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn (Builder $query, $classId) => $query->whereRelation('classSubject', 'school_class_id', $classId),
                    ))
                    ->visible($user->isStaffMember()),
            ])
            ->recordActions([
                Action::make('takeRollCall')
                    ->label(fn (TimetableSlot $record): string => $this->sessionOf($record) ? 'Voir l\'appel' : 'Faire l\'appel')
                    ->icon(Heroicon::OutlinedHandRaised)
                    ->button()
                    ->action(fn (TimetableSlot $record) => $this->takeRollCall($record)),
            ]);
    }

    /**
     * Open the roll call of the lesson, creating it from the timetable the first time.
     */
    protected function takeRollCall(TimetableSlot $slot): void
    {
        $session = $this->sessionOf($slot);

        if ($session === null) {
            $schoolClass = $slot->classSubject->schoolClass;
            $termId = array_key_first(Term::openOptions($schoolClass->school_year_id));
            $startsAt = substr($slot->starts_at, 0, 5);

            if ($termId === null) {
                Notification::make()->danger()->title('Tous les trimestres sont clôturés : l\'appel ne peut plus être fait.')->send();

                return;
            }

            if (AttendanceSession::overlapsAnother($schoolClass->getKey(), today()->toDateString(), $startsAt, $slot->hours)) {
                Notification::make()->danger()->title("La classe {$schoolClass->name} a déjà un appel sur ce créneau.")->send();

                return;
            }

            $session = AttendanceSession::create([
                'class_subject_id' => $slot->class_subject_id,
                'term_id' => $termId,
                'held_on' => today(),
                'starts_at' => $startsAt,
                'hours' => $slot->hours,
                'taken_by' => static::user()->getKey(),
            ]);
        }

        $this->redirect(AttendanceSessionResource::getUrl('take', ['record' => $session]));
    }

    protected function sessionOf(TimetableSlot $slot): ?AttendanceSession
    {
        $this->todaySessions ??= AttendanceSession::query()
            ->whereDate('held_on', today())
            ->get()
            ->keyBy(fn (AttendanceSession $session): string => $session->class_subject_id.'|'.TimetableSlot::minutes($session->starts_at));

        return $this->todaySessions->get($slot->class_subject_id.'|'.TimetableSlot::minutes($slot->starts_at));
    }

    protected static function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
