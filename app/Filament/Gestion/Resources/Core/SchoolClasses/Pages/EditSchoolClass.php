<?php

namespace App\Filament\Gestion\Resources\Core\SchoolClasses\Pages;

use App\Filament\Gestion\Resources\Core\SchoolClasses\SchoolClassResource;
use App\Models\Core\SchoolClass;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSchoolClass extends EditRecord
{
    protected static string $resource = SchoolClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (SchoolClass $record): bool => $record->enrollments()->exists()),
        ];
    }
}
