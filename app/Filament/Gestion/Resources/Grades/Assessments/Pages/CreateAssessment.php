<?php

namespace App\Filament\Gestion\Resources\Grades\Assessments\Pages;

use App\Filament\Gestion\Resources\Grades\Assessments\AssessmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssessment extends CreateRecord
{
    protected static string $resource = AssessmentResource::class;

    /**
     * Go straight to grade entry once the assessment exists.
     */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
