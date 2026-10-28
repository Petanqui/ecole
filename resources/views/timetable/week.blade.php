{{--
    A school week, one column per day, lessons by start time.
    $slots: TimetableSlot collection; $details: fn (TimetableSlot): array of lines shown under the subject.
--}}
@php
    use App\Enums\Timetable\Weekday;

    $days = collect(Weekday::cases())->filter(fn (Weekday $day): bool => $day !== Weekday::Saturday || $slots->contains('weekday', Weekday::Saturday));
    $byDay = $slots->sortBy(fn ($slot) => $slot->starts_at)->groupBy(fn ($slot) => $slot->weekday->value);
@endphp
<div style="overflow-x: auto;">
    <table style="width: 100%; min-width: 48rem; border-collapse: collapse; table-layout: fixed; font-size: 0.875rem;">
        <thead>
            <tr>
                @foreach ($days as $day)
                    <th style="padding: 0.5rem; text-align: left; border-bottom: 2px solid rgba(127, 127, 127, 0.3);">{{ $day->getLabel() }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                @foreach ($days as $day)
                    <td style="vertical-align: top; padding: 0.375rem;">
                        @forelse ($byDay->get($day->value, collect()) as $slot)
                            <div style="margin-bottom: 0.5rem; padding: 0.5rem; border-radius: 0.5rem; border: 1px solid rgba(127, 127, 127, 0.25); background: rgba(14, 165, 233, 0.08);">
                                <div style="font-size: 0.75rem; opacity: 0.75;">{{ $slot->time_slot }}</div>
                                <div style="font-weight: 600;">{{ $slot->classSubject->subject->name }}</div>
                                @foreach (array_filter($details($slot)) as $line)
                                    <div style="font-size: 0.75rem; opacity: 0.75;">{{ $line }}</div>
                                @endforeach
                            </div>
                        @empty
                            <div style="opacity: 0.5;">—</div>
                        @endforelse
                    </td>
                @endforeach
            </tr>
        </tbody>
    </table>
</div>
