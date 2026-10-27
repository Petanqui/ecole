<?php

namespace App\Filament\Gestion\Pages\Grades;

use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\Grades\ClassSubject;
use App\Models\Grades\Term;
use App\Models\User;
use App\Services\Grades\TermResults;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Term averages and ranks of a class. Staff see every class, a teacher the classes they teach in.
 *
 * @property-read Schema $form
 */
class TermResultsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.gestion.pages.grades.term-results-page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Notes';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Résultats';

    protected static ?string $title = 'Résultats du trimestre';

    protected static ?string $slug = 'resultats';

    /**
     * @var array{school_class_id?: int|string|null, term_id?: int|string|null}
     */
    public array $selection = [];

    protected ?TermResults $results = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && ($user->isStaffMember() || $user->isTeacher());
    }

    public function mount(): void
    {
        $termId = SchoolYear::current()->first()?->terms()->where('is_closed', false)->value('id')
            ?? SchoolYear::current()->first()?->terms()->orderByDesc('number')->value('id');

        $this->form->fill([
            'school_class_id' => array_key_first($this->classOptions()),
            'term_id' => $termId,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('selection')
            ->components([
                Grid::make(2)->schema([
                    Select::make('school_class_id')
                        ->label('Classe')
                        ->options(fn (): array => $this->classOptions())
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn () => $this->resetTable()),
                    Select::make('term_id')
                        ->label('Trimestre')
                        ->options(fn (): array => SchoolYear::current()->first()?->terms
                            ->mapWithKeys(fn (Term $term): array => [$term->id => $term->name.($term->is_closed ? ' (clôturé)' : '')])
                            ->all() ?? [])
                        ->live()
                        ->afterStateUpdated(fn () => $this->resetTable()),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        $results = $this->results();

        $subjectColumns = ($results?->subjects ?? collect())->map(fn (ClassSubject $classSubject): TextColumn => TextColumn::make("subject_{$classSubject->id}")
            ->label($classSubject->subject->code)
            ->tooltip("{$classSubject->subject->name} — coef. {$classSubject->coefficient}")
            ->alignCenter()
            ->placeholder('—'))
            ->all();

        return $table
            ->records(fn (): array => $this->rows())
            ->paginated(false)
            ->emptyStateHeading('Aucun élève inscrit dans cette classe')
            ->columns([
                TextColumn::make('rank')
                    ->label('Rang')
                    ->placeholder('—'),
                TextColumn::make('student')
                    ->label('Élève'),
                ...$subjectColumns,
                TextColumn::make('average')
                    ->label('Moyenne / '.($results->gradingScale ?? 20))
                    ->weight('bold')
                    ->alignCenter()
                    ->placeholder('—'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleTermClosure')
                ->label(fn (): string => ($this->selectedTerm()?->is_closed ? 'Rouvrir le ' : 'Clôturer le ').$this->selectedTerm()?->name)
                ->icon(fn (): Heroicon => $this->selectedTerm()?->is_closed ? Heroicon::OutlinedLockOpen : Heroicon::OutlinedLockClosed)
                ->color(fn (): string => $this->selectedTerm()?->is_closed ? 'gray' : 'warning')
                ->visible(fn (): bool => $this->selectedTerm() !== null && auth()->user()->isStaffMember())
                ->requiresConfirmation()
                ->modalDescription(fn (): string => $this->selectedTerm()?->is_closed
                    ? 'Les enseignants pourront à nouveau modifier les notes de ce trimestre, dans toutes les classes.'
                    : 'Les enseignants ne pourront plus modifier les notes de ce trimestre, dans toutes les classes. Le personnel le pourra toujours.')
                ->action(function (): void {
                    $term = $this->selectedTerm();
                    $term->update(['is_closed' => ! $term->is_closed]);

                    Notification::make()->success()->title($term->name.($term->is_closed ? ' clôturé' : ' rouvert'))->send();
                }),
        ];
    }

    /**
     * Current-year classes the user may see: all for staff, those they teach in for a teacher.
     *
     * @return array<int, string>
     */
    protected function classOptions(): array
    {
        /** @var User $user */
        $user = auth()->user();

        return SchoolClass::query()
            ->whereRelation('schoolYear', 'is_current', true)
            ->when(! $user->isStaffMember(), fn ($query) => $query->whereRelation('classSubjects', 'teacher_id', $user->getKey()))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    protected function selectedTerm(): ?Term
    {
        return Term::query()
            ->whereRelation('schoolYear', 'is_current', true)
            ->find($this->selection['term_id'] ?? null);
    }

    /**
     * Results of the selected class and term, only when the user may see that class.
     */
    protected function results(): ?TermResults
    {
        $classId = (int) ($this->selection['school_class_id'] ?? 0);
        $term = $this->selectedTerm();

        if ($term === null || ! array_key_exists($classId, $this->classOptions())) {
            return null;
        }

        if ($this->results?->schoolClass->getKey() !== $classId || $this->results->term->isNot($term)) {
            $this->results = new TermResults(SchoolClass::findOrFail($classId), $term);
        }

        return $this->results;
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    protected function rows(): array
    {
        $results = $this->results();

        if ($results === null) {
            return [];
        }

        return $results->students->map(function (array $row) use ($results): array {
            $cells = [
                'rank' => $row['rank'] === null ? null : TermResults::formatRank($row['rank'], $row['is_tied']),
                'student' => $row['student']->full_name,
                'average' => TermResults::formatAverage($row['average']),
            ];

            foreach ($results->subjects as $classSubject) {
                $cells["subject_{$classSubject->id}"] = TermResults::formatAverage($row['subjects'][$classSubject->id]['average']);
            }

            return $cells;
        })->all();
    }
}
