<x-filament-panels::page>
    {{ $this->form }}

    <x-filament::section>
        @include('filament.gestion.billing.cash-summary', ['report' => $this->report()])
    </x-filament::section>

    {{ $this->table }}
</x-filament-panels::page>
