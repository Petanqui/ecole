<x-filament-panels::page>
    @php
        $announcements = $this->getAnnouncements();
    @endphp

    @if ($announcements->isEmpty())
        <x-filament::section>
            <p style="opacity: 0.7;">Aucune annonce pour l'instant.</p>
        </x-filament::section>
    @endif

    @foreach ($announcements as $announcement)
        <x-filament::section>
            <x-slot name="heading">
                {{ $announcement->title }}
                @if (in_array($announcement->id, $newAnnouncementIds, true))
                    <x-filament::badge color="danger" style="display: inline-flex; margin-left: 0.5rem;">Nouveau</x-filament::badge>
                @endif
            </x-slot>
            <x-slot name="description">
                {{ $announcement->created_at->translatedFormat('j F Y à H:i') }}
                — {{ $announcement->audience_label }}
                @if ($announcement->author)
                    — {{ $announcement->author->isStaffMember() ? 'Administration' : $announcement->author->name }}
                @endif
            </x-slot>

            <div style="white-space: pre-line;">{{ $announcement->body }}</div>
        </x-filament::section>
    @endforeach
</x-filament-panels::page>
