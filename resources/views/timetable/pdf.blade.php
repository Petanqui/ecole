@php
    use App\Enums\Timetable\Weekday;

    $days = collect(Weekday::cases())->filter(fn (Weekday $day): bool => $day !== Weekday::Saturday || $slots->contains('weekday', Weekday::Saturday));
    $byDay = $slots->sortBy(fn ($slot) => $slot->starts_at)->groupBy(fn ($slot) => $slot->weekday->value);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Emploi du temps — {{ $schoolClass->name }}</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #111; }
        table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .school-name { font-size: 13pt; font-weight: bold; text-transform: uppercase; }
        .muted { color: #555; font-size: 8pt; }
        .title { text-align: center; font-size: 13pt; font-weight: bold; margin: 10px 0; padding: 5px; border: 1.5px solid #111; }
        .week { table-layout: fixed; }
        .week th { background: #e8e8e8; border: 1px solid #333; padding: 4px; }
        .week td { border: 1px solid #333; padding: 4px; vertical-align: top; }
        .lesson { margin-bottom: 5px; padding: 4px; border: 1px solid #999; background: #f3f3f3; }
        .time { font-size: 8pt; color: #555; }
        .subject { font-weight: bold; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <div class="school-name">{{ config('school.name') }}</div>
                @if (config('school.address'))<div class="muted">{{ config('school.address') }}</div>@endif
                @if (config('school.phone'))<div class="muted">{{ config('school.phone') }}</div>@endif
            </td>
            <td style="text-align: right;">
                <div>Année scolaire <strong>{{ $schoolClass->schoolYear->name }}</strong></div>
            </td>
        </tr>
    </table>

    <div class="title">EMPLOI DU TEMPS — {{ $schoolClass->name }}</div>

    <table class="week">
        <tr>
            @foreach ($days as $day)
                <th>{{ $day->getLabel() }}</th>
            @endforeach
        </tr>
        <tr>
            @foreach ($days as $day)
                <td>
                    @foreach ($byDay->get($day->value, collect()) as $slot)
                        <div class="lesson">
                            <div class="time">{{ $slot->time_slot }}</div>
                            <div class="subject">{{ $slot->classSubject->subject->name }}</div>
                            @if ($slot->classSubject->teacher)<div class="muted">{{ $slot->classSubject->teacher->name }}</div>@endif
                            @if ($slot->room)<div class="muted">{{ $slot->room->name }}</div>@endif
                        </div>
                    @endforeach
                </td>
            @endforeach
        </tr>
    </table>
</body>
</html>
