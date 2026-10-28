@php
    use App\Services\Billing\StudentAccount;

    $cell = 'padding: 0.5rem 0.75rem 0.5rem 0; border-bottom: 1px solid rgba(127, 127, 127, 0.2);';
    $amountCell = $cell.' text-align: right; white-space: nowrap;';
    $hasDiscount = $account->discount() > 0;
@endphp
<div style="overflow-x: auto;">
    <table style="width: 100%; font-size: 0.875rem; border-collapse: collapse;">
        <thead>
            <tr style="text-align: left; opacity: 0.7;">
                <th style="{{ $cell }}">Échéance</th>
                <th style="{{ $cell }}">Frais</th>
                @if ($hasDiscount)
                    <th style="{{ $amountCell }}">Réduction</th>
                @endif
                <th style="{{ $amountCell }}">Montant</th>
                <th style="{{ $amountCell }}">Payé</th>
                <th style="{{ $amountCell }}">Reste</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($account->dues as $due)
                <tr style="{{ $due['remaining'] > 0 && $due['due_on']->isPast() ? 'color: rgb(220, 38, 38);' : '' }}">
                    <td style="{{ $cell }} white-space: nowrap;">{{ $due['due_on']->format('d/m/Y') }}</td>
                    <td style="{{ $cell }}">{{ $due['label'] }}</td>
                    @if ($hasDiscount)
                        <td style="{{ $amountCell }}">{{ $due['discount'] > 0 ? '− '.StudentAccount::formatAmount($due['discount']) : '—' }}</td>
                    @endif
                    <td style="{{ $amountCell }}">{{ StudentAccount::formatAmount($due['amount']) }}</td>
                    <td style="{{ $amountCell }}">{{ StudentAccount::formatAmount($due['paid']) }}</td>
                    <td style="{{ $amountCell }} font-weight: 600;">{{ $due['remaining'] > 0 ? StudentAccount::formatAmount($due['remaining']) : 'Soldé' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $hasDiscount ? 6 : 5 }}" style="padding: 1rem; text-align: center; opacity: 0.7;">Aucun tarif ne s'applique à cet élève (Facturation → Tarifs).</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="font-weight: 600;">
                <td style="{{ $cell }}" colspan="2">Total</td>
                @if ($hasDiscount)
                    <td style="{{ $amountCell }}">− {{ StudentAccount::formatAmount($account->discount()) }}</td>
                @endif
                <td style="{{ $amountCell }}">{{ StudentAccount::formatAmount($account->total()) }}</td>
                <td style="{{ $amountCell }}">{{ StudentAccount::formatAmount($account->paid()) }}</td>
                <td style="{{ $amountCell }}">{{ StudentAccount::formatAmount($account->balance()) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
