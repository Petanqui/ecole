<?php

namespace App\Filament\Gestion\Resources\Core\Students\Pages;

use App\Filament\Gestion\Resources\Core\Students\StudentResource;
use App\Models\Core\Student;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (Student $record): bool => $record->enrollments()->exists()),
        ];
    }
}
