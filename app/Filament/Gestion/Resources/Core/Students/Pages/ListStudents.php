<?php

namespace App\Filament\Gestion\Resources\Core\Students\Pages;

use App\Filament\Gestion\Resources\Core\Students\StudentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
