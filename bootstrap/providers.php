<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\GestionPanelProvider;
use App\Providers\Filament\PortailPanelProvider;

return [
    AppServiceProvider::class,
    GestionPanelProvider::class,
    PortailPanelProvider::class,
];
