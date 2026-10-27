<?php

namespace App\Filament\Gestion\Resources\Billing\Fees\Pages;

use App\Filament\Gestion\Resources\Billing\Fees\FeeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFees extends ListRecords
{
    protected static string $resource = FeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
