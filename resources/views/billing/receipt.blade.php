@php
    use App\Services\Billing\StudentAccount;
    use Illuminate\Support\Number;

    $enrollment = $payment->enrollment;
    $student = $enrollment->student;
    $amount = fn (int $value): string => StudentAccount::formatAmount($value);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reçu {{ $payment->receipt_number }}</title>
    <style>
        @page { margin: 12mm 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; color: #111; }
        table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .header td.meta { width: 38%; text-align: right; white-space: nowrap; }
        .school-name { font-size: 12pt; font-weight: bold; text-transform: uppercase; }
        .muted { color: #555; font-size: 8.5pt; }
        .title { text-align: center; font-size: 12pt; font-weight: bold; margin: 10px 0 8px; padding: 5px; border: 1.5px solid #111; }
        .details td { padding: 3px 4px; width: 50%; vertical-align: top; }
        .label { color: #555; }
        .amount { margin-top: 8px; border: 1px solid #333; padding: 8px; }
        .big { font-size: 14pt; font-weight: bold; }
        .account { margin-top: 8px; }
        .account td { border: 1px solid #333; padding: 4px 6px; }
        .num { text-align: right; white-space: nowrap; }
        .cancelled { margin-top: 8px; padding: 6px; border: 2px solid #b00; color: #b00; font-weight: bold; text-align: center; }
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
                <div>Année scolaire <strong>{{ $enrollment->schoolClass->schoolYear->name }}</strong></div>
                <div style="margin-top: 4px;">Reçu n° <strong>{{ $payment->receipt_number }}</strong></div>
                <div class="muted">du {{ $payment->paid_on->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <div class="title">REÇU DE PAIEMENT</div>

    @if ($payment->isCancelled())
        <div class="cancelled">
            PAIEMENT ANNULÉ le {{ $payment->cancelled_at->format('d/m/Y') }} — {{ $payment->cancellation_reason }}
        </div>
    @endif

    <table class="details">
        <tr>
            <td><span class="label">Élève :</span> <strong>{{ $student->full_name }}</strong> <span class="muted" style="white-space: nowrap;">({{ $student->code }})</span></td>
            <td><span class="label">Classe :</span> <strong>{{ $enrollment->schoolClass->name }}</strong></td>
        </tr>
        <tr>
            <td><span class="label">Versé par :</span> {{ $payment->payer_name ?? '—' }}</td>
            <td>
                <span class="label">Mode :</span> {{ $payment->method->getLabel() }}
                @if ($payment->reference)<span class="muted">— {{ $payment->method->referenceLabel() }} : {{ $payment->reference }}</span>@endif
            </td>
        </tr>
    </table>

    <div class="amount">
        <div>Montant reçu : <span class="big">{{ $amount($payment->amount) }}</span></div>
        <div class="muted" style="margin-top: 3px;">Arrêté le présent reçu à la somme de {{ Number::spell($payment->amount, locale: 'fr') }} francs CFA.</div>
    </div>

    @unless ($payment->isCancelled())
        <table class="account">
            <tr>
                <td>Total des frais de l'année{{ $discount > 0 ? ' (après '.$amount($discount).' de réduction)' : '' }}</td>
                <td class="num">{{ $amount($total) }}</td>
            </tr>
            <tr>
                <td>Total payé à ce jour (ce reçu compris)</td>
                <td class="num">{{ $amount($paidToDate) }}</td>
            </tr>
            <tr>
                <td><strong>Reste à payer</strong></td>
                <td class="num"><strong>{{ $amount(max(0, $total - $paidToDate)) }}</strong></td>
            </tr>
        </table>
    @endunless

    <table class="signatures">
        <tr>
            <td>
                <div class="muted">Reçu par</div>
                <div>{{ $payment->receiver?->name ?? '—' }}</div>
            </td>
            <td>
                <div class="muted">Cachet de l'établissement</div>
            </td>
        </tr>
    </table>
</body>
</html>
