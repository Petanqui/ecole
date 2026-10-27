<?php

namespace App\Filament\Gestion\Resources\Grades\Assessments\Pages;

use App\Filament\Gestion\Resources\Grades\Assessments\AssessmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssessments extends ListRecords
{
    protected static string $resource = AssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
