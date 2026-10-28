<x-filament-panels::page>
    {{ $this->form }}

    @php
        $slots = $this->getWeekSlots();
    @endphp

    <x-filament::section>
        @include('timetable.week', [
            'slots' => $slots,
            'details' => fn ($slot) => [$slot->classSubject->schoolClass->name, $slot->room?->name],
        ])

        <p style="margin-top: 0.75rem; opacity: 0.7; font-size: 0.875rem;">
            {{ $slots->sum('hours') }} heure(s) de cours par semaine.
        </p>
    </x-filament::section>
</x-filament-panels::page>
