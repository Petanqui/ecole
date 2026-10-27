<?php

namespace App\Filament\Gestion\Resources\Billing\Fees\Pages;

use App\Filament\Gestion\Resources\Billing\Fees\FeeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFee extends EditRecord
{
    protected static string $resource = FeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
