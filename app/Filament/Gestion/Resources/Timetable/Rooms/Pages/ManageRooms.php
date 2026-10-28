<?php

namespace App\Filament\Gestion\Resources\Timetable\Rooms\Pages;

use App\Filament\Gestion\Resources\Timetable\Rooms\RoomResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRooms extends ManageRecords
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
