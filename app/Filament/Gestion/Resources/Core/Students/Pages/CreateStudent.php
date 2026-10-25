<?php

namespace App\Filament\Gestion\Resources\Core\Students\Pages;

use App\Filament\Gestion\Resources\Core\Students\StudentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;
}
