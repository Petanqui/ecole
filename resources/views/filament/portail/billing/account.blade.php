@php
    use App\Services\Billing\StudentAccount;

    $cell = 'padding: 0.5rem 0.75rem 0.5rem 0; border-bottom: 1px solid rgba(127, 127, 127, 0.2);';
@endphp
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <div>
        <h3 style="font-weight: 600; margin-bottom: 0.5rem;">Échéancier</h3>
        @include('filament.gestion.billing.dues', ['account' => $account])
    </div>

    <div>
        <h3 style="font-weight: 600; margin-bottom: 0.5rem;">Paiements</h3>
        @if ($account->payments->isEmpty())
            <p style="opacity: 0.7;">Aucun paiement enregistré pour cette année.</p>
        @else
            <div style="overflow-x: auto;">
                <table style="width: 100%; font-size: 0.875rem; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; opacity: 0.7;">
                            <th style="{{ $cell }}">Date</th>
                            <th style="{{ $cell }}">Reçu</th>
                            <th style="{{ $cell }}">Mode</th>
                            <th style="{{ $cell }} text-align: right;">Montant</th>
                            <th style="{{ $cell }}"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($account->payments->sortByDesc('paid_on') as $payment)
                            <tr>
                                <td style="{{ $cell }} white-space: nowrap;">{{ $payment->paid_on->format('d/m/Y') }}</td>
                                <td style="{{ $cell }} white-space: nowrap;">{{ $payment->receipt_number }}</td>
                                <td style="{{ $cell }}">{{ $payment->method->getLabel() }}</td>
                                <td style="{{ $cell }} text-align: right; white-space: nowrap;">{{ StudentAccount::formatAmount($payment->amount) }}</td>
                                <td style="{{ $cell }} text-align: right;">
                                    <a href="{{ route('filament.portail.receipts.show', $payment) }}" target="_blank" style="color: rgb(2, 132, 199); text-decoration: underline;">Télécharger le reçu</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
