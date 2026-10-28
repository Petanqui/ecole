<div style="display: flex; flex-direction: column; gap: 1rem;">
    @if ($slots->isEmpty())
        <p style="opacity: 0.7;">L'emploi du temps de la classe n'est pas encore disponible.</p>
    @else
        @include('timetable.week', [
            'slots' => $slots,
            'details' => fn ($slot) => [$slot->classSubject->teacher?->name, $slot->room?->name],
        ])

        <div>
            <a href="{{ route('filament.portail.timetables.class', $schoolClass) }}" target="_blank" style="color: rgb(2, 132, 199); text-decoration: underline;">Télécharger en PDF</a>
        </div>
    @endif
</div>
