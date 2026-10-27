<?php

namespace App\Filament\Gestion\Resources\Attendance\Attendances\Tables;

use App\Enums\Attendance\AttendanceStatus;
use App\Models\Attendance\Attendance;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Absences and late arrivals, most recent lesson first, with their justification.
 */
class AttendancesTable
{
    public static function configure(Table $table, bool $showsStudent = true): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendances.attendance_session_id')
                ->select('attendances.*')
                ->with(['session.classSubject.subject', 'enrollment.student', 'enrollment.schoolClass', 'justifier']))
            ->defaultSort(fn (Builder $query) => $query->orderByDesc('attendance_sessions.held_on')->orderByDesc('attendance_sessions.starts_at'))
            ->columns([
                TextColumn::make('session.held_on')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->description(fn (Attendance $record): string => $record->session->time_slot),
                TextColumn::make('enrollment.student.full_name')
                    ->label('Élève')
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('enrollment.student', fn (Builder $query) => $query
                        ->whereLike('last_name', "%{$search}%")
                        ->orWhereLike('first_name', "%{$search}%")
                        ->orWhereLike('code', "%{$search}%")))
                    ->visible($showsStudent),
                TextColumn::make('enrollment.schoolClass.name')
                    ->label('Classe')
                    ->visible($showsStudent),
                TextColumn::make('session.classSubject.subject.name')
                    ->label('Matière'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('missed_hours')
                    ->label('Heures')
                    ->alignCenter()
                    ->state(fn (Attendance $record): ?int => $record->missedHours() ?: null)
                    ->placeholder('—'),
                TextColumn::make('justification')
                    ->label('Justification')
                    ->state(fn (Attendance $record): ?string => $record->isJustified() ? ($record->justification ?: 'Justifié(e)') : null)
                    ->description(fn (Attendance $record): ?string => $record->isJustified()
                        ? 'le '.$record->justified_at->format('d/m/Y').($record->justifier ? " par {$record->justifier->name}" : '')
                        : null)
                    ->color('success')
                    ->placeholder('Non justifiée')
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('school_class')
                    ->label('Classe')
                    ->options(fn (): array => SchoolClass::query()
                        ->whereRelation('schoolYear', 'is_current', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn (Builder $query, $classId) => $query->whereRelation('enrollment', 'school_class_id', $classId),
                    ))
                    ->visible($showsStudent),
                SelectFilter::make('school_year')
                    ->label('Année scolaire')
                    ->options(fn (): array => SchoolYear::query()->orderByDesc('starts_on')->pluck('name', 'id')->all())
                    ->default(fn (): ?int => SchoolYear::current()->value('id'))
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn (Builder $query, $schoolYearId) => $query->whereRelation('enrollment', 'school_year_id', $schoolYearId),
                    ))
                    ->visible($showsStudent),
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        AttendanceStatus::Absent->value => AttendanceStatus::Absent->getLabel(),
                        AttendanceStatus::Late->value => AttendanceStatus::Late->getLabel(),
                    ]),
                TernaryFilter::make('justified')
                    ->label('Justification')
                    ->placeholder('Toutes')
                    ->trueLabel('Justifiées')
                    ->falseLabel('Non justifiées')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('attendances.justified_at'),
                        false: fn (Builder $query) => $query->whereNull('attendances.justified_at'),
                    ),
                Filter::make('held_on')
                    ->schema([
                        DatePicker::make('from')->label('Du'),
                        DatePicker::make('until')->label('Au'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'], fn (Builder $query, $date) => $query->whereDate('attendance_sessions.held_on', '>=', $date))
                        ->when($data['until'], fn (Builder $query, $date) => $query->whereDate('attendance_sessions.held_on', '<=', $date))),
            ])
            ->recordActions([
                Action::make('justify')
                    ->label('Justifier')
                    ->icon(Heroicon::OutlinedDocumentCheck)
                    ->color('success')
                    ->visible(fn (Attendance $record): bool => ! $record->isJustified())
                    ->modalHeading(fn (Attendance $record): string => "Justifier — {$record->enrollment->student->full_name}")
                    ->schema([static::reasonInput()])
                    ->action(function (Attendance $record, array $data): void {
                        $record->justify(static::user(), $data['reason']);

                        Notification::make()->success()->title('Justification enregistrée')->send();
                    }),
                Action::make('unjustify')
                    ->label('Retirer la justification')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('gray')
                    ->visible(fn (Attendance $record): bool => $record->isJustified())
                    ->requiresConfirmation()
                    ->action(fn (Attendance $record) => $record->unjustify()),
            ])
            ->toolbarActions([
                BulkAction::make('justify')
                    ->label('Justifier')
                    ->icon(Heroicon::OutlinedDocumentCheck)
                    ->color('success')
                    ->modalHeading('Justifier les absences et retards sélectionnés')
                    ->modalDescription('Par exemple toutes les absences couvertes par un même certificat médical.')
                    ->schema([static::reasonInput()])
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records, array $data): void {
                        $records->each(fn (Attendance $record) => $record->justify(static::user(), $data['reason']));

                        Notification::make()->success()->title("{$records->count()} justification(s) enregistrée(s)")->send();
                    }),
            ]);
    }

    protected static function reasonInput(): TextInput
    {
        return TextInput::make('reason')
            ->label('Motif')
            ->placeholder('Ex. « Certificat médical du 20/10 »')
            ->required()
            ->maxLength(255);
    }

    protected static function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
