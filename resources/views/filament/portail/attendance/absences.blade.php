@php
    $cell = 'padding: 0.5rem 0.75rem 0.5rem 0; border-bottom: 1px solid rgba(127, 127, 127, 0.2);';
@endphp
<div style="display: flex; flex-direction: column; gap: 1rem;">
    <p><strong>Cette année :</strong> {{ $summary->describe() }}</p>

    @if ($attendances->isEmpty())
        <p style="opacity: 0.7;">Aucune absence ni aucun retard cette année.</p>
    @else
        <div style="overflow-x: auto;">
            <table style="width: 100%; font-size: 0.875rem; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; opacity: 0.7;">
                        <th style="{{ $cell }}">Date</th>
                        <th style="{{ $cell }}">Cours</th>
                        <th style="{{ $cell }}">Statut</th>
                        <th style="{{ $cell }}">Justification</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($attendances as $attendance)
                        <tr>
                            <td style="{{ $cell }} white-space: nowrap;">
                                {{ $attendance->session->held_on->format('d/m/Y') }}
                                <div style="opacity: 0.7;">{{ $attendance->session->time_slot }}</div>
                            </td>
                            <td style="{{ $cell }}">{{ $attendance->session->classSubject->subject->name }}</td>
                            <td style="{{ $cell }} white-space: nowrap;">
                                {{ $attendance->status->getLabel() }}
                                @if ($attendance->missedHours() > 0)
                                    <div style="opacity: 0.7;">{{ $attendance->missedHours() }} h</div>
                                @endif
                            </td>
                            <td style="{{ $cell }}">
                                @if ($attendance->isJustified())
                                    <span style="color: rgb(22, 163, 74);">{{ $attendance->justification ?: 'Justifié(e)' }}</span>
                                @else
                                    <span style="color: rgb(220, 38, 38);">Non justifiée</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p style="opacity: 0.7; font-size: 0.875rem;">Pour justifier une absence, présentez le justificatif (certificat médical, mot signé…) au secrétariat de l'école.</p>
    @endif
</div>
