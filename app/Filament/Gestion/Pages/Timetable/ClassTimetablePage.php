<?php

namespace App\Filament\Gestion\Pages\Timetable;

use App\Enums\Timetable\Weekday;
use App\Filament\Gestion\Concerns\RestrictedToStaffMembers;
use App\Models\Core\SchoolClass;
use App\Models\Grades\ClassSubject;
use App\Models\Timetable\Room;
use App\Models\Timetable\TimetableSlot;
use BackedEnum;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

/**
 * The weekly timetable of a class: its lessons as a week, and the list to add, change or remove them.
 *
 * @property-read Schema $form
 */
class ClassTimetablePage extends Page implements HasTable
{
    use InteractsWithTable;
    use RestrictedToStaffMembers;

    protected string $view = 'filament.gestion.pages.timetable.class-timetable-page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Emploi du temps';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Par classe';

    protected static ?string $title = 'Emploi du temps des classes';

    protected static ?string $slug = 'emploi-du-temps';

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
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn () => $this->resetTable()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => TimetableSlot::query()
                ->whereRelation('classSubject', 'school_class_id', $this->selectedClassId())
                ->with(['classSubject.subject', 'classSubject.teacher', 'room']))
            ->defaultSort(fn (Builder $query) => $query->orderBy('weekday')->orderBy('starts_at'))
            ->paginated(false)
            ->heading('Cours de la semaine')
            ->emptyStateHeading('Aucun cours pour cette classe')
            ->columns([
                TextColumn::make('weekday')
                    ->label('Jour'),
                TextColumn::make('time_slot')
                    ->label('Horaire'),
                TextColumn::make('classSubject.subject.name')
                    ->label('Matière'),
                TextColumn::make('classSubject.teacher.name')
                    ->label('Enseignant')
                    ->placeholder('Non attribué'),
                TextColumn::make('room.name')
                    ->label('Salle')
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter un cours')
                    ->model(TimetableSlot::class)
                    ->modalHeading('Ajouter un cours')
                    ->schema(fn (): array => $this->slotFields())
                    ->visible(fn (): bool => $this->selectedClassId() !== null),
            ])
            ->recordActions([
                EditAction::make()
                    ->schema(fn (): array => $this->slotFields()),
                DeleteAction::make(),
            ]);
    }

    /**
     * @return Collection<int, TimetableSlot>
     */
    public function getWeekSlots(): Collection
    {
        return TimetableSlot::query()
            ->whereRelation('classSubject', 'school_class_id', $this->selectedClassId())
            ->with(['classSubject.subject', 'classSubject.teacher', 'room'])
            ->get();
    }

    /**
     * @return array<int, Component>
     */
    protected function slotFields(): array
    {
        return [
            Select::make('class_subject_id')
                ->label('Matière')
                ->options(fn (): array => ClassSubject::query()
                    ->where('school_class_id', $this->selectedClassId())
                    ->with(['subject', 'teacher'])
                    ->get()
                    ->sortBy('subject.sort_order')
                    ->mapWithKeys(fn (ClassSubject $classSubject): array => [
                        $classSubject->id => $classSubject->subject->name.($classSubject->teacher ? " — {$classSubject->teacher->name}" : ''),
                    ])
                    ->all())
                ->required(),
            Select::make('weekday')
                ->label('Jour')
                ->options(Weekday::class)
                ->required(),
            TimePicker::make('starts_at')
                ->label('Début')
                ->seconds(false)
                ->required()
                ->rules([
                    fn (Get $get, ?TimetableSlot $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                        $classSubject = ClassSubject::with(['schoolClass', 'teacher'])->find($get('class_subject_id'));
                        $weekday = $get('weekday') instanceof Weekday ? $get('weekday') : Weekday::tryFrom((int) $get('weekday'));

                        if ($classSubject === null || $weekday === null || (int) $get('hours') < 1) {
                            return;
                        }

                        $conflicts = TimetableSlot::conflicts($classSubject, $weekday, (string) $value, (int) $get('hours'), $get('room_id') ? (int) $get('room_id') : null, $record?->getKey());

                        if ($conflicts !== []) {
                            $fail(implode(' ', $conflicts));
                        }
                    },
                ]),
            TextInput::make('hours')
                ->label('Durée')
                ->integer()
                ->minValue(1)
                ->maxValue(4)
                ->default(1)
                ->suffix('heure(s)')
                ->required(),
            Select::make('room_id')
                ->label('Salle')
                ->options(fn (): array => Room::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->placeholder('Aucune'),
        ];
    }

    /**
     * Classes of the current year.
     *
     * @return array<int, string>
     */
    protected function classOptions(): array
    {
        return SchoolClass::query()
            ->whereRelation('schoolYear', 'is_current', true)
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
