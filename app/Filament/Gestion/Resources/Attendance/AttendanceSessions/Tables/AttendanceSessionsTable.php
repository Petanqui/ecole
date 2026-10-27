<?php

namespace App\Filament\Gestion\Resources\Attendance\AttendanceSessions\Tables;

use App\Enums\Attendance\AttendanceStatus;
use App\Filament\Gestion\Resources\Attendance\AttendanceSessions\AttendanceSessionResource;
use App\Models\Attendance\AttendanceSession;
use App\Models\Core\SchoolClass;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendanceSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['classSubject.schoolClass', 'classSubject.subject', 'term', 'taker'])
                ->withCount([
                    'attendances as absences_count' => fn (Builder $query) => $query->where('status', AttendanceStatus::Absent),
                    'attendances as late_arrivals_count' => fn (Builder $query) => $query->where('status', AttendanceStatus::Late),
                ]))
            ->defaultSort(fn (Builder $query) => $query->orderByDesc('held_on')->orderByDesc('starts_at'))
            ->recordUrl(fn (AttendanceSession $record): string => AttendanceSessionResource::getUrl('take', ['record' => $record]))
            ->columns([
                TextColumn::make('held_on')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('time_slot')
                    ->label('Horaire'),
                TextColumn::make('classSubject.schoolClass.name')
                    ->label('Classe'),
                TextColumn::make('classSubject.subject.name')
                    ->label('Matière'),
                TextColumn::make('absences_count')
                    ->label('Absents')
                    ->alignCenter()
                    ->color(fn (int $state): ?string => $state > 0 ? 'danger' : null),
                TextColumn::make('late_arrivals_count')
                    ->label('Retards')
                    ->alignCenter()
                    ->color(fn (int $state): ?string => $state > 0 ? 'warning' : null),
                TextColumn::make('term.name')
                    ->label('Trimestre')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('taker.name')
                    ->label('Fait par')
                    ->placeholder('—')
                    ->toggleable(),
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
                        fn (Builder $query, $classId) => $query->whereRelation('classSubject', 'school_class_id', $classId),
                    )),
                SelectFilter::make('term')
                    ->label('Trimestre')
                    ->options([1 => '1er trimestre', 2 => '2e trimestre', 3 => '3e trimestre'])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn (Builder $query, $number) => $query->whereRelation('term', 'number', $number),
                    )),
            ])
            ->recordActions([
                Action::make('take')
                    ->label('Appel')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->url(fn (AttendanceSession $record): string => AttendanceSessionResource::getUrl('take', ['record' => $record])),
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('Les absences et retards notés à cet appel seront supprimés.'),
            ]);
    }
}
