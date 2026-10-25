<?php

namespace App\Filament\Gestion\Resources\Core\Families\Pages;

use App\Filament\Gestion\Resources\Core\Families\FamilyResource;
use App\Models\Core\Family;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFamily extends EditRecord
{
    protected static string $resource = FamilyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (Family $record): bool => $record->students()->exists()),
        ];
    }
}
