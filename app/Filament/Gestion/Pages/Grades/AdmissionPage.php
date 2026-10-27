<?php

namespace App\Filament\Gestion\Pages\Grades;

use App\Enums\Core\GradeLevel;
use App\Enums\Grades\AdmissionExam;
use App\Filament\Gestion\Concerns\RestrictedToStaffMembers;
use App\Models\Core\Enrollment;
use App\Models\Core\SchoolClass;
use App\Services\Grades\TermResults;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * CM2 admission to 6e: the four exam scores (out of 20) of each student, their MAP and the decision.
 *
 * @property-read Schema $form
 */
class AdmissionPage extends Page implements HasTable
{
    use InteractsWithTable;
    use RestrictedToStaffMembers;

    protected string $view = 'filament.gestion.pages.grades.admission-page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Notes';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Admission en 6e';

    protected static ?string $title = 'Admission en 6e (CM2)';

    protected static ?string $slug = 'admission-6e';

    /**
     * @var array{school_class_id?: int|string|null}
     */
    public array $selection = [];

    public function mount(): void
    {
        $this->form->fill(['school_class_id' => array_key_first($this->classOptions())]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('selection')
            ->components([
                Select::make('school_class_id')
                    ->label('Classe')
                    ->options(fn (): array => $this->classOptions())
                    ->live()
                    ->afterStateUpdated(fn () => $this->resetTable()),
            ]);
    }

    public function table(Table $table): Table
    {
        $examColumns = collect(AdmissionExam::cases())->map(fn (AdmissionExam $exam): TextInputColumn => TextInputColumn::make($exam->value)
            ->label($exam->getLabel().' / 20')
            ->type('number')
            ->step(0.25)
            ->rules(['nullable', 'numeric', 'min:0', 'max:20'])
            ->getStateUsing(fn (Enrollment $record): ?float => $record->admissionExamScore($exam))
            ->updateStateUsing(fn (Enrollment $record, ?string $state) => $record->setAdmissionExamScore($exam, $state === null ? null : (float) $state)))
            ->all();

        return $table
            ->query(fn (): Builder => Enrollment::query()
                ->where('school_class_id', $this->selectedClassId())
                ->with(['student', 'admissionExamScores'])
                ->orderBy(fn ($query) => $query->select('last_name')->from('students')->whereColumn('students.id', 'enrollments.student_id'))
                ->orderBy(fn ($query) => $query->select('first_name')->from('students')->whereColumn('students.id', 'enrollments.student_id')))
            ->paginated(false)
            ->emptyStateHeading('Aucun élève inscrit dans cette classe')
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('Élève'),
                ...$examColumns,
                TextColumn::make('admission_average')
                    ->label('MAP / 20')
                    ->weight('bold')
                    ->alignCenter()
                    ->state(fn (Enrollment $record): ?string => TermResults::formatAverage($record->admissionAverage()))
                    ->placeholder('—'),
                TextColumn::make('decision')
                    ->label('Décision')
                    ->badge()
                    ->state(fn (Enrollment $record): ?string => match (true) {
                        $record->admissionAverage() === null => null,
                        $record->admissionAverage() >= config('grades.cm2_admission_average') => 'Admis(e)',
                        default => 'Non admis(e)',
                    })
                    ->color(fn (string $state): string => $state === 'Admis(e)' ? 'success' : 'danger')
                    ->placeholder('—'),
            ]);
    }

    /**
     * CM2 classes of the current year.
     *
     * @return array<int, string>
     */
    protected function classOptions(): array
    {
        return SchoolClass::query()
            ->whereRelation('schoolYear', 'is_current', true)
            ->where('level', GradeLevel::Cm2)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    protected function selectedClassId(): ?int
    {
        $classId = (int) ($this->selection['school_class_id'] ?? 0);

        return array_key_exists($classId, $this->classOptions()) ? $classId : null;
    }
}
