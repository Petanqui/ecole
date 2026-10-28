@php
    use App\Services\Billing\StudentAccount;

    $cell = 'padding: 0.5rem 0.75rem 0.5rem 0; border-bottom: 1px solid rgba(127, 127, 127, 0.2);';
    $amountCell = $cell.' text-align: right; white-space: nowrap;';
    $cancelled = $report->cancelledPayments();
@endphp
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <div>
        <div style="font-size: 0.875rem; opacity: 0.7;">Encaissé {{ $report->periodLabel() }}</div>
        <div style="font-size: 1.75rem; font-weight: 700;">{{ StudentAccount::formatAmount($report->total()) }}</div>
        <div style="font-size: 0.875rem; opacity: 0.7;">
            {{ $report->validPayments()->count() }} paiement(s)
            @if ($cancelled->isNotEmpty())
                — {{ $cancelled->count() }} annulé(s) ({{ StudentAccount::formatAmount($cancelled->sum('amount')) }}), non compté(s)
            @endif
        </div>
    </div>

    @if ($report->validPayments()->isNotEmpty())
        <div style="display: flex; flex-wrap: wrap; gap: 2rem;">
            <div style="flex: 1 1 18rem; overflow-x: auto;">
                <table style="width: 100%; font-size: 0.875rem; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; opacity: 0.7;">
                            <th style="{{ $cell }}">Mode de paiement</th>
                            <th style="{{ $amountCell }}">Paiements</th>
                            <th style="{{ $amountCell }}">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report->byMethod() as $row)
                            <tr>
                                <td style="{{ $cell }}">{{ $row['method']->getLabel() }}</td>
                                <td style="{{ $amountCell }}">{{ $row['count'] }}</td>
                                <td style="{{ $amountCell }} font-weight: 600;">{{ StudentAccount::formatAmount($row['total']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="flex: 1 1 26rem; overflow-x: auto;">
                <table style="width: 100%; font-size: 0.875rem; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; opacity: 0.7;">
                            <th style="{{ $cell }}">Caissier</th>
                            <th style="{{ $amountCell }}">Paiements</th>
                            <th style="{{ $amountCell }}">Espèces à remettre</th>
                            <th style="{{ $amountCell }}">Autres modes</th>
                            <th style="{{ $amountCell }}">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report->byCashier() as $row)
                            <tr>
                                <td style="{{ $cell }}">{{ $row['cashier'] }}</td>
                                <td style="{{ $amountCell }}">{{ $row['count'] }}</td>
                                <td style="{{ $amountCell }} font-weight: 600;">{{ StudentAccount::formatAmount($row['cash']) }}</td>
                                <td style="{{ $amountCell }}">{{ StudentAccount::formatAmount($row['other']) }}</td>
                                <td style="{{ $amountCell }}">{{ StudentAccount::formatAmount($row['total']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
