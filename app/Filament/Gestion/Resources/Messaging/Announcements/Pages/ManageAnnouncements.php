<?php

namespace App\Filament\Gestion\Resources\Messaging\Announcements\Pages;

use App\Filament\Gestion\Resources\Messaging\Announcements\AnnouncementResource;
use App\Models\Core\SchoolYear;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAnnouncements extends ManageRecords
{
    protected static string $resource = AnnouncementResource::class;

    /**
     * Announcements are published at once, for the current school year.
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Publier une annonce')
                ->modalHeading('Publier une annonce')
                ->modalSubmitActionLabel('Publier')
                ->createAnother(false)
                ->mutateDataUsing(fn (array $data): array => [
                    ...$data,
                    'author_id' => auth()->id(),
                    'school_year_id' => SchoolYear::current()->value('id'),
                ]),
        ];
    }
}
