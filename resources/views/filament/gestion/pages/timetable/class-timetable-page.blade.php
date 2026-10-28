<x-filament-panels::page>
    {{ $this->form }}

    <x-filament::section>
        @include('timetable.week', [
            'slots' => $this->getWeekSlots(),
            'details' => fn ($slot) => [$slot->classSubject->teacher?->name, $slot->room?->name],
        ])
    </x-filament::section>

    {{ $this->table }}
</x-filament-panels::page>
