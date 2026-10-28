@php
    use App\Services\Billing\StudentAccount;

    $hasOverdue = $alerts->contains(fn (array $alert): bool => $alert['overdue'] > 0);
@endphp
<x-filament-widgets::widget>
    <x-filament::section
        :heading="$hasOverdue ? 'Paiements en retard' : 'Prochaines échéances'"
        :icon="$hasOverdue ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-bell'"
        :icon-color="$hasOverdue ? 'danger' : 'warning'"
    >
        <ul style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.875rem;">
            @foreach ($alerts as $alert)
                <li>
                    <strong>{{ $alert['student'] }}</strong>
                    @if ($alert['overdue'] > 0)
                        : <span style="color: rgb(220, 38, 38); font-weight: 600;">{{ StudentAccount::formatAmount($alert['overdue']) }} en retard</span>
                        (échéance du {{ $alert['overdue_since'] }})
                        @if ($alert['conversation_url'])
                            — <x-filament::link :href="$alert['conversation_url']">voir le message de l'école</x-filament::link>
                        @endif
                    @endif
                    @if ($alert['upcoming'])
                        <div style="opacity: 0.8;">{{ $alert['overdue'] > 0 ? 'Puis ' : '' }}{{ $alert['upcoming'] }}</div>
                    @endif
                </li>
            @endforeach
        </ul>

        <p style="margin-top: 1rem; font-size: 0.875rem; opacity: 0.7;">
            Paiement au secrétariat : espèces, Mobile Money, virement ou chèque. Le détail est dans « Compte », sous « Mes enfants ».
            @if ($hasOverdue) Si vous avez déjà payé, merci de ne pas tenir compte de ce rappel. @endif
        </p>
    </x-filament::section>
</x-filament-widgets::widget>
