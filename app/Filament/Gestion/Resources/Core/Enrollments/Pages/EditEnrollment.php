<?php

namespace App\Filament\Gestion\Resources\Core\Enrollments\Pages;

use App\Filament\Gestion\Resources\Core\Enrollments\EnrollmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEnrollment extends EditRecord
{
    protected static string $resource = EnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
