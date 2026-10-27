<x-filament-panels::page>
    <x-filament::section>
        @include('messaging.thread', ['messages' => $this->getThreadMessages(), 'viewer' => $this->getViewer()])
    </x-filament::section>

    <form wire:submit="send">
        {{ $this->form }}

        <div style="margin-top: 1rem;">
            <x-filament::button type="submit" icon="heroicon-o-paper-airplane">
                Envoyer
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
