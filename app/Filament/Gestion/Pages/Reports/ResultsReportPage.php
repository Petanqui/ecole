<?php

namespace App\Filament\Gestion\Pages\Reports;

use App\Filament\Gestion\Concerns\RestrictedToStaffMembers;
use App\Filament\Gestion\Pages\Grades\TermResultsPage;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\Grades\Assessment;
use App\Models\Grades\Term;
use App\Services\Grades\TermResults;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * The results of every class for a term: class average, highest and lowest, and how many students
 * reach the average (5/10 in primary, 10/20 in secondary).
 *
 * @property-read Schema $form
 */
class ResultsReportPage extends Page implements HasTable
{
    use InteractsWithTable;
    use RestrictedToStaffMembers;

    protected string $view = 'filament.gestion.pages.reports.results-report-page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Rapports';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Bilan des résultats';

    protected static ?string $title = 'Bilan des résultats par classe';

    protected static ?string $slug = 'bilan-resultats';

    /**
     * @var array{term_id?: int|string|null}
     */
    public array $selection = [];

    /**
     * @var Collection<int, array<string, mixed>>|null
     */
    protected ?Collection $rows = null;

    /**
     * Opens on the latest term that already has assessments.
     */
    public function mount(): void
    {
        $terms = $this->terms();
        $latestWithMarks = $terms->last(fn (Term $term): bool => Assessment::query()->where('term_id', $term->getKey())->exists());

        $this->form->fill(['term_id' => ($latestWithMarks ?? $terms->first())?->getKey()]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('selection')
            ->components([
                Select::make('term_id')
                    ->label('Trimestre')
                    ->options(fn (): array => $this->terms()->mapWithKeys(fn (Term $term): array => [$term->getKey() => $term->name])->all())
                    ->selectablePlaceholder(false)
                    ->live()
                    ->afterStateUpdated(function (): void {
                        $this->rows = null;
                        $this->resetTable();
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => $this->rows()->all())
            ->paginated(false)
            ->emptyStateHeading('Aucune classe cette année')
            ->columns([
                TextColumn::make('class')
                    ->label('Classe'),
                TextColumn::make('headcount')
                    ->label('Effectif')
                    ->alignCenter(),
                TextColumn::make('ranked')
                    ->label('Classés')
                    ->alignCenter(),
                TextColumn::make('average')
                    ->label('Moyenne de la classe')
                    ->alignCenter()
                    ->weight('bold')
                    ->placeholder('—'),
                TextColumn::make('highest')
                    ->label('Plus forte')
                    ->alignCenter()
                    ->placeholder('—'),
                TextColumn::make('lowest')
                    ->label('Plus faible')
                    ->alignCenter()
                    ->placeholder('—'),
                TextColumn::make('pass_rate')
                    ->label('Ont la moyenne')
                    ->alignEnd()
                    ->formatStateUsing(fn (float $state, array $record): string => number_format($state, 1, ',', ' ')." % ({$record['passed']}/{$record['ranked']})")
                    ->color(fn (float $state): string => $state >= 50 ? 'success' : 'danger')
                    ->placeholder('—'),
                TextColumn::make('top_student')
                    ->label('Premier(e)')
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('details')
                    ->label('Détail')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (array $record): string => TermResultsPage::getUrl(['classe' => $record['id'], 'trimestre' => $this->selectedTerm()?->getKey()])),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Exporter (CSV)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (): StreamedResponse => $this->exportCsv()),
        ];
    }

    /**
     * One row per class of the current year, in class order.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function rows(): Collection
    {
        $term = $this->selectedTerm();

        if ($term === null) {
            return collect();
        }

        return $this->rows ??= SchoolClass::query()
            ->where('school_year_id', $term->school_year_id)
            ->orderBy('name')
            ->get()
            ->map(function (SchoolClass $schoolClass) use ($term): array {
                $results = new TermResults($schoolClass, $term);
                $ranked = $results->students->filter(fn (array $row): bool => $row['average'] !== null);
                $passingAverage = $results->gradingScale / 2;
                $passed = $ranked->filter(fn (array $row): bool => $row['average'] >= $passingAverage)->count();
                $top = $ranked->first(fn (array $row): bool => $row['rank'] === 1);
                $scale = " / {$results->gradingScale}";

                return [
                    'id' => $schoolClass->getKey(),
                    'class' => $schoolClass->name,
                    'headcount' => $results->students->count(),
                    'ranked' => $ranked->count(),
                    'average' => ($average = TermResults::formatAverage($results->classStatistics['average'])) ? $average.$scale : null,
                    'highest' => ($highest = TermResults::formatAverage($results->classStatistics['highest'])) ? $highest.$scale : null,
                    'lowest' => ($lowest = TermResults::formatAverage($results->classStatistics['lowest'])) ? $lowest.$scale : null,
                    'passed' => $passed,
                    'pass_rate' => $ranked->isEmpty() ? null : round(100 * $passed / $ranked->count(), 1),
                    'top_student' => $top ? $top['student']->full_name.($top['is_tied'] ? ' (ex æquo)' : '') : null,
                ];
            })
            ->keyBy('id');
    }

    protected function exportCsv(): StreamedResponse
    {
        $rows = $this->rows();
        $term = $this->selectedTerm();

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\u{FEFF}");
            fputcsv($output, ['Classe', 'Effectif', 'Classés', 'Moyenne de la classe', 'Plus forte', 'Plus faible', 'Ont la moyenne', 'Taux (%)', 'Premier(e)'], ';');

            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['class'],
                    $row['headcount'],
                    $row['ranked'],
                    $row['average'],
                    $row['highest'],
                    $row['lowest'],
                    $row['passed'],
                    $row['pass_rate'] === null ? '' : number_format($row['pass_rate'], 1, ',', ''),
                    $row['top_student'],
                ], ';');
            }

            fclose($output);
        }, 'bilan-resultats-trimestre-'.($term?->number ?? 0).'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return Collection<int, Term>
     */
    protected function terms(): Collection
    {
        return SchoolYear::current()->first()?->terms ?? collect();
    }

    protected function selectedTerm(): ?Term
    {
        return $this->terms()->firstWhere('id', (int) ($this->selection['term_id'] ?? 0));
    }
}
