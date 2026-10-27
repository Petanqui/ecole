<?php

namespace App\Filament\Gestion\Resources\Grades\Assessments\Pages;

use App\Filament\Gestion\Resources\Grades\Assessments\AssessmentResource;
use App\Models\Grades\Assessment;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssessment extends EditRecord
{
    protected static string $resource = AssessmentResource::class;

    protected static ?string $title = 'Saisie des notes';

    /**
     * Students enrolled after the assessment was created appear in the grade entry table too.
     */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var Assessment $assessment */
        $assessment = $this->getRecord();
        $assessment->syncStudents();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalDescription('Toutes les notes de cette évaluation seront supprimées.'),
        ];
    }
}
