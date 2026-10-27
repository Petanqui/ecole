@php
    use App\Enums\Core\GradeLevel;
    use App\Services\Grades\Appreciation;
    use App\Services\Grades\TermResults;

    $scale = $results->gradingScale;
    $average = fn (?float $value): string => TermResults::formatAverage($value) ?? '—';
    $rank = fn (?int $value, bool $tied = false): string => $value === null ? '—' : TermResults::formatRank($value, $tied);
    $headcount = $results->students->count();
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bulletin — {{ $schoolClass->name }} — {{ $term->name }}</title>
    <style>
        @page { margin: 14mm 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; color: #111; }
        .card { page-break-after: always; }
        .card:last-child { page-break-after: auto; }
        table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .school-name { font-size: 13pt; font-weight: bold; text-transform: uppercase; }
        .muted { color: #555; font-size: 8.5pt; }
        .title { text-align: center; font-size: 13pt; font-weight: bold; margin: 10px 0 6px; padding: 5px; border: 1.5px solid #111; }
        .identity td { padding: 2px 4px; }
        .label { color: #555; }
        .grades { margin-top: 8px; }
        .grades th, .grades td { border: 1px solid #333; padding: 3px 4px; }
        .grades th { background: #e8e8e8; font-size: 8.5pt; }
        .num { text-align: center; white-space: nowrap; }
        .totals td { font-weight: bold; background: #f3f3f3; }
        .summary { margin-top: 10px; }
        .summary td { border: 1px solid #333; padding: 5px 6px; vertical-align: top; width: 50%; }
        .big { font-size: 12pt; font-weight: bold; }
        .distinction { font-size: 11pt; font-weight: bold; text-transform: uppercase; }
        .signatures { margin-top: 18px; }
        .signatures td { width: 50%; height: 60px; vertical-align: top; text-align: center; }
    </style>
</head>
<body>
@foreach ($rows as $studentId => $row)
    @php
        $student = $row['student'];
        $totalCoefficients = 0;
        $totalPoints = 0.0;
        $annualRow = $annual?->students[$studentId] ?? null;
    @endphp
    <div class="card">
        <table class="header">
            <tr>
                <td>
                    <div class="school-name">{{ config('school.name') }}</div>
                    @if (config('school.address'))<div class="muted">{{ config('school.address') }}</div>@endif
                    @if (config('school.phone') || config('school.email'))
                        <div class="muted">{{ collect([config('school.phone'), config('school.email')])->filter()->join(' — ') }}</div>
                    @endif
                </td>
                <td style="text-align: right;">
                    <div>République de Côte d'Ivoire</div>
                    <div class="muted">Union – Discipline – Travail</div>
                    <div style="margin-top: 4px;">Année scolaire <strong>{{ $schoolClass->schoolYear->name }}</strong></div>
                </td>
            </tr>
        </table>

        <div class="title">BULLETIN DE NOTES — {{ mb_strtoupper($term->name) }}</div>

        <table class="identity">
            <tr>
                <td><span class="label">Nom et prénoms :</span> <strong>{{ $student->full_name }}</strong></td>
                <td><span class="label">Classe :</span> <strong>{{ $schoolClass->name }}</strong></td>
            </tr>
            <tr>
                <td><span class="label">Né(e) le :</span> {{ $student->birth_date->format('d/m/Y') }}{{ $student->birth_place ? ' à '.$student->birth_place : '' }}</td>
                <td><span class="label">Effectif :</span> {{ $headcount }}</td>
            </tr>
            <tr>
                <td><span class="label">Sexe :</span> {{ $student->gender->getLabel() }}</td>
                <td><span class="label">Matricule :</span> {{ $student->mena_number ?? '—' }} <span class="muted">({{ $student->code }})</span></td>
            </tr>
        </table>

        <table class="grades">
            <thead>
                <tr>
                    <th style="text-align: left;">Discipline</th>
                    <th>Moy. / {{ $scale }}</th>
                    <th>Coef.</th>
                    <th>Moy. × coef.</th>
                    <th>Rang</th>
                    <th>Moy. classe</th>
                    <th style="text-align: left;">Appréciation</th>
                    <th style="text-align: left;">Professeur</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($results->subjects as $classSubject)
                    @php
                        $subject = $row['subjects'][$classSubject->id];
                        if ($subject['average'] !== null) {
                            $totalCoefficients += $classSubject->coefficient;
                            $totalPoints += $subject['average'] * $classSubject->coefficient;
                        }
                    @endphp
                    <tr>
                        <td>{{ $classSubject->subject->name }}</td>
                        <td class="num"><strong>{{ $average($subject['average']) }}</strong></td>
                        <td class="num">{{ $classSubject->coefficient }}</td>
                        <td class="num">{{ $subject['average'] === null ? '—' : $average($subject['average'] * $classSubject->coefficient) }}</td>
                        <td class="num">{{ $rank($subject['rank']) }}</td>
                        <td class="num">{{ $average($results->subjectStatistics[$classSubject->id]['average']) }}</td>
                        <td>{{ Appreciation::forSubject($subject['average'], $scale) ?? '' }}</td>
                        <td class="muted">{{ $classSubject->teacher?->name }}</td>
                    </tr>
                @endforeach
                <tr class="totals">
                    <td>Totaux</td>
                    <td></td>
                    <td class="num">{{ $totalCoefficients }}</td>
                    <td class="num">{{ $average($totalPoints) }}</td>
                    <td colspan="4"></td>
                </tr>
            </tbody>
        </table>

        <table class="summary">
            <tr>
                <td>
                    <div>Moyenne trimestrielle : <span class="big">{{ $average($row['average']) }} / {{ $scale }}</span></div>
                    <div>Rang : <strong>{{ $rank($row['rank'], $row['is_tied']) }}</strong> sur {{ $results->rankedCount() }}</div>
                    <div class="muted" style="margin-top: 4px;">
                        Moyenne de la classe : {{ $average($results->classStatistics['average']) }} —
                        plus forte : {{ $average($results->classStatistics['highest']) }} —
                        plus faible : {{ $average($results->classStatistics['lowest']) }}
                    </div>
                    @if ($annualRow)
                        <div style="margin-top: 6px; border-top: 1px solid #999; padding-top: 4px;">
                            @foreach ($annualRow['terms'] as $number => $termAverage)
                                T{{ $number }} : {{ $average($termAverage) }}@if (! $loop->last) — @endif
                            @endforeach
                            <div>Moyenne annuelle : <span class="big">{{ $average($annualRow['average']) }} / {{ $scale }}</span></div>
                            <div>Rang annuel : <strong>{{ $rank($annualRow['rank'], $annualRow['is_tied']) }}</strong></div>
                            @if ($schoolClass->level === GradeLevel::Cm2)
                                <div>Moyenne d'admission en 6e (MAP) : <strong>{{ $average($annualRow['admission_average']) }} / 20</strong></div>
                                @if ($annualRow['is_promoted'] !== null)
                                    <div>Décision : <strong>{{ $annualRow['is_promoted'] ? 'Admis(e) en 6e' : 'Non admis(e) en 6e' }}</strong></div>
                                @endif
                            @elseif ($annualRow['is_promoted'] !== null)
                                <div>Décision : <strong>{{ $annualRow['is_promoted'] ? 'Admis(e) en classe supérieure' : 'Redouble' }}</strong></div>
                            @endif
                        </div>
                    @endif
                </td>
                <td>
                    <div class="label">Assiduité du trimestre</div>
                    <div style="margin-bottom: 8px;"><strong>{{ $attendance->get($studentId)?->describe() ?? 'Aucune absence — aucun retard' }}</strong></div>
                    <div class="label">Distinction / sanction</div>
                    <div class="distinction">{{ Appreciation::distinction($annualRow['average'] ?? $row['average'], $scale) ?? '—' }}</div>
                    <div class="label" style="margin-top: 8px;">Observations du conseil de classe</div>
                    <div style="height: 34px;"></div>
                </td>
            </tr>
        </table>

        <table class="signatures">
            <tr>
                <td>Le Professeur principal</td>
                <td>{{ config('school.head_title') }}</td>
            </tr>
        </table>

        <div class="muted" style="text-align: right;">Édité le {{ now()->format('d/m/Y') }}</div>
    </div>
@endforeach
</body>
</html>
