<?php

namespace App\Filament\Gestion\Resources\Billing\Discounts\Pages;

use App\Filament\Gestion\Resources\Billing\Discounts\DiscountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDiscounts extends ManageRecords
{
    protected static string $resource = DiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
