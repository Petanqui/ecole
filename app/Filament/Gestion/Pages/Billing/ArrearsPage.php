<?php

namespace App\Filament\Gestion\Pages\Billing;

use App\Filament\Gestion\Concerns\RestrictedToStaffMembers;
use App\Filament\Gestion\Resources\Core\Enrollments\EnrollmentResource;
use App\Models\Core\Enrollment;
use App\Models\Core\Guardian;
use App\Models\Core\SchoolClass;
use App\Services\Billing\StudentAccount;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Students of the current year who are behind with their payments, with their parents' phone numbers.
 *
 * @property-read Schema $form
 */
class ArrearsPage extends Page implements HasTable
{
    use InteractsWithTable;
    use RestrictedToStaffMembers;

    protected string $view = 'filament.gestion.pages.billing.arrears-page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'Facturation';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Impayés';

    protected static ?string $title = 'Impayés';

    protected static ?string $slug = 'impayes';

    /**
     * @var array{school_class_id?: int|string|null, as_of?: string|null, includes_up_to_date?: bool}
     */
    public array $criteria = [];

    /**
     * @var Collection<int, array<string, mixed>>|null
     */
    protected ?Collection $rows = null;

    public function mount(): void
    {
        $this->form->fill(['as_of' => now()->toDateString(), 'includes_up_to_date' => false]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('criteria')
            ->components([
                Grid::make(3)->schema([
                    Select::make('school_class_id')
                        ->label('Classe')
                        ->options(fn (): array => SchoolClass::query()
                            ->whereRelation('schoolYear', 'is_current', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->placeholder('Toutes les classes')
                        ->live()
                        ->afterStateUpdated(fn () => $this->refreshRows()),
                    DatePicker::make('as_of')
                        ->label('Retards au')
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn () => $this->refreshRows()),
                    Toggle::make('includes_up_to_date')
                        ->label('Afficher aussi les élèves à jour')
                        ->inline(false)
                        ->live()
                        ->afterStateUpdated(fn () => $this->refreshRows()),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => $this->rows()->all())
            ->paginated(false)
            ->heading(fn (): string => 'Total en retard : '.StudentAccount::formatAmount($this->rows()->sum('overdue'))
                .' — reste à payer sur l\'année : '.StudentAccount::formatAmount($this->rows()->sum('balance')))
            ->emptyStateHeading('Aucun impayé')
            ->emptyStateDescription('Tous les élèves sont à jour de leurs paiements à cette date.')
            ->columns([
                TextColumn::make('student')
                    ->label('Élève')
                    ->description(fn (array $record): string => $record['code']),
                TextColumn::make('class')
                    ->label('Classe'),
                TextColumn::make('guardians')
                    ->label('Parents')
                    ->listWithLineBreaks()
                    ->placeholder('—'),
                TextColumn::make('total')
                    ->label('Total de l\'année')
                    ->formatStateUsing(fn (int $state): string => StudentAccount::formatAmount($state)),
                TextColumn::make('paid')
                    ->label('Payé')
                    ->formatStateUsing(fn (int $state): string => StudentAccount::formatAmount($state)),
                TextColumn::make('overdue')
                    ->label('En retard')
                    ->weight('bold')
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'success')
                    ->formatStateUsing(fn (int $state): string => StudentAccount::formatAmount($state)),
                TextColumn::make('balance')
                    ->label('Reste à payer')
                    ->formatStateUsing(fn (int $state): string => StudentAccount::formatAmount($state)),
                TextColumn::make('next_due')
                    ->label('Prochaine échéance')
                    ->placeholder('Soldé'),
            ])
            ->recordActions([
                Action::make('account')
                    ->label('Compte')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->url(fn (array $record): string => EnrollmentResource::getUrl('edit', ['record' => $record['id']])),
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

    protected function refreshRows(): void
    {
        $this->rows = null;
        $this->resetTable();
    }

    protected function asOf(): Carbon
    {
        return Carbon::parse($this->criteria['as_of'] ?? now());
    }

    /**
     * One row per current-year enrollment, the largest arrears first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function rows(): Collection
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        $enrollments = Enrollment::query()
            ->whereRelation('schoolYear', 'is_current', true)
            ->when($this->criteria['school_class_id'] ?? null, fn ($query, $classId) => $query->where('school_class_id', $classId))
            ->with(['student.family.guardians'])
            ->get();

        $asOf = $this->asOf();

        return $this->rows = StudentAccount::forEnrollments($enrollments)
            ->map(function (StudentAccount $account) use ($asOf): array {
                $student = $account->enrollment->student;
                $nextDue = $account->nextUnpaidDue();

                return [
                    'id' => $account->enrollment->getKey(),
                    'student' => $student->full_name,
                    'code' => $student->code,
                    'class' => $account->enrollment->schoolClass->name,
                    'guardians' => $student->family->guardians
                        ->map(fn (Guardian $guardian): string => "{$guardian->first_name} {$guardian->last_name} : {$guardian->phone}")
                        ->all(),
                    'total' => $account->total(),
                    'paid' => $account->paid(),
                    'overdue' => $account->overdue($asOf),
                    'balance' => $account->balance(),
                    'next_due' => $nextDue === null
                        ? null
                        : $nextDue['due_on']->format('d/m/Y').' : '.StudentAccount::formatAmount($nextDue['remaining']),
                ];
            })
            ->when(! ($this->criteria['includes_up_to_date'] ?? false), fn (Collection $rows) => $rows->filter(fn (array $row): bool => $row['overdue'] > 0))
            ->sortBy([['overdue', 'desc'], ['class', 'asc'], ['student', 'asc']])
            ->keyBy('id');
    }

    protected function exportCsv(): StreamedResponse
    {
        $rows = $this->rows();

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\u{FEFF}");
            fputcsv($output, ['Élève', 'Code', 'Classe', 'Parents', 'Total de l\'année', 'Payé', 'En retard', 'Reste à payer', 'Prochaine échéance'], ';');

            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['student'],
                    $row['code'],
                    $row['class'],
                    implode(' / ', $row['guardians']),
                    $row['total'],
                    $row['paid'],
                    $row['overdue'],
                    $row['balance'],
                    $row['next_due'] ?? 'Soldé',
                ], ';');
            }

            fclose($output);
        }, 'impayes-'.$this->asOf()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
