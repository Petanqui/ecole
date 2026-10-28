@php
    use App\Services\Billing\StudentAccount;

    $amount = fn (int $value): string => StudentAccount::formatAmount($value);
    $cancelled = $report->cancelledPayments();
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>État de caisse {{ $report->periodLabel() }}</title>
    <style>
        @page { margin: 12mm 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #111; }
        table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .header td.meta { width: 38%; text-align: right; white-space: nowrap; }
        .school-name { font-size: 12pt; font-weight: bold; text-transform: uppercase; }
        .muted { color: #555; font-size: 8pt; }
        .title { text-align: center; font-size: 12pt; font-weight: bold; margin: 10px 0 8px; padding: 5px; border: 1.5px solid #111; }
        .total { margin-bottom: 8px; border: 1px solid #333; padding: 8px; }
        .big { font-size: 14pt; font-weight: bold; }
        h2 { font-size: 9.5pt; margin: 10px 0 4px; }
        .grid th, .grid td { border: 1px solid #333; padding: 3px 5px; text-align: left; }
        .grid th { background: #eee; }
        .num { text-align: right !important; white-space: nowrap; }
        .cancelled td { color: #888; text-decoration: line-through; }
        .signatures { margin-top: 16px; }
        .signatures td { width: 50%; height: 55px; vertical-align: top; text-align: center; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <div class="school-name">{{ config('school.name') }}</div>
                @if (config('school.address'))<div class="muted">{{ config('school.address') }}</div>@endif
                @if (config('school.phone') || config('school.email'))
                    <div class="muted">{{ collect([config('school.phone'), config('school.email')])->filter()->join(' — ') }}</div>
                @endif
            </td>
            <td class="meta">
                <div>Imprimé le {{ now()->format('d/m/Y à H:i') }}</div>
                <div class="muted">par {{ auth()->user()->name }}</div>
            </td>
        </tr>
    </table>

    <div class="title">ÉTAT DE CAISSE {{ mb_strtoupper($report->periodLabel()) }}</div>

    @if ($report->receiverId !== null || $report->method !== null)
        <p class="muted">
            {{ collect([
                $report->receiverId !== null ? 'Caissier : '.($cashierName ?? '—') : null,
                $report->method !== null ? 'Mode de paiement : '.$report->method->getLabel() : null,
            ])->filter()->join(' — ') }}
        </p>
    @endif

    <div class="total">
        Total encaissé : <span class="big">{{ $amount($report->total()) }}</span>
        <span class="muted">— {{ $report->validPayments()->count() }} paiement(s)@if ($cancelled->isNotEmpty()), {{ $cancelled->count() }} annulé(s) non compté(s) ({{ $amount($cancelled->sum('amount')) }})@endif</span>
    </div>

    @if ($report->validPayments()->isNotEmpty())
        <h2>Par mode de paiement</h2>
        <table class="grid">
            <tr><th>Mode</th><th class="num">Paiements</th><th class="num">Montant</th></tr>
            @foreach ($report->byMethod() as $row)
                <tr><td>{{ $row['method']->getLabel() }}</td><td class="num">{{ $row['count'] }}</td><td class="num">{{ $amount($row['total']) }}</td></tr>
            @endforeach
        </table>

        <h2>Par caissier</h2>
        <table class="grid">
            <tr><th>Caissier</th><th class="num">Paiements</th><th class="num">Espèces à remettre</th><th class="num">Autres modes</th><th class="num">Total</th></tr>
            @foreach ($report->byCashier() as $row)
                <tr>
                    <td>{{ $row['cashier'] }}</td>
                    <td class="num">{{ $row['count'] }}</td>
                    <td class="num"><strong>{{ $amount($row['cash']) }}</strong></td>
                    <td class="num">{{ $amount($row['other']) }}</td>
                    <td class="num">{{ $amount($row['total']) }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h2>Détail des paiements</h2>
    <table class="grid">
        <tr><th>Reçu</th><th>Date</th><th>Élève</th><th>Classe</th><th>Mode</th><th>Reçu par</th><th class="num">Montant</th></tr>
        @forelse ($report->payments as $payment)
            <tr @class(['cancelled' => $payment->isCancelled()])>
                <td>{{ $payment->receipt_number }}</td>
                <td>{{ $payment->paid_on->format('d/m/Y') }}</td>
                <td>{{ $payment->enrollment->student->full_name }}</td>
                <td>{{ $payment->enrollment->schoolClass->name }}</td>
                <td>{{ $payment->method->getLabel() }}@if ($payment->reference)<br><span class="muted">{{ $payment->reference }}</span>@endif</td>
                <td>{{ $payment->receiver?->name ?? '—' }}</td>
                <td class="num">{{ $amount($payment->amount) }}@if ($payment->isCancelled())<br><span class="muted">annulé</span>@endif</td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align: center;">Aucun paiement sur cette période.</td></tr>
        @endforelse
    </table>

    <table class="signatures">
        <tr>
            <td><div class="muted">Le caissier</div></td>
            <td><div class="muted">Le responsable financier</div></td>
        </tr>
    </table>
</body>
</html>
