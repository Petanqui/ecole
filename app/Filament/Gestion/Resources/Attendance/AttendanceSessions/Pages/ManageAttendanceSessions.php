<?php

namespace App\Filament\Gestion\Resources\Attendance\AttendanceSessions\Pages;

use App\Filament\Gestion\Resources\Attendance\AttendanceSessions\AttendanceSessionResource;
use App\Models\Attendance\AttendanceSession;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAttendanceSessions extends ManageRecords
{
    protected static string $resource = AttendanceSessionResource::class;

    /**
     * Opens the roll call of the class as soon as the lesson is recorded.
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Faire l\'appel')
                ->modalHeading('Faire l\'appel')
                ->modalSubmitActionLabel('Continuer')
                ->createAnother(false)
                ->mutateDataUsing(fn (array $data): array => [...$data, 'taken_by' => auth()->id()])
                ->successNotification(null)
                ->successRedirectUrl(fn (AttendanceSession $record): string => AttendanceSessionResource::getUrl('take', ['record' => $record])),
        ];
    }
}
