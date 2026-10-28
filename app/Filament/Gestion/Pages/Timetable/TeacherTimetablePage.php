<?php

namespace App\Filament\Gestion\Pages\Timetable;

use App\Enums\Core\UserRole;
use App\Models\Timetable\TimetableSlot;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

/**
 * The week of a teacher: their own for a teacher, any teacher's for staff.
 *
 * @property-read Schema $form
 */
class TeacherTimetablePage extends Page
{
    protected string $view = 'filament.gestion.pages.timetable.teacher-timetable-page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Emploi du temps';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'emploi-du-temps-enseignant';

    /**
     * @var array{teacher_id?: int|string|null}
     */
    public array $selection = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && ($user->isStaffMember() || $user->isTeacher());
    }

    public static function getNavigationLabel(): string
    {
        return static::user()->isTeacher() ? 'Mon emploi du temps' : 'Par enseignant';
    }

    public function getTitle(): string
    {
        return static::user()->isTeacher() ? 'Mon emploi du temps' : 'Emploi du temps des enseignants';
    }

    public function mount(): void
    {
        $this->form->fill(['teacher_id' => array_key_first($this->teacherOptions())]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('selection')
            ->components([
                Select::make('teacher_id')
                    ->label('Enseignant')
                    ->options(fn (): array => $this->teacherOptions())
                    ->searchable()
                    ->live()
                    ->hidden(fn (): bool => static::user()->isTeacher()),
            ]);
    }

    /**
     * @return Collection<int, TimetableSlot>
     */
    public function getWeekSlots(): Collection
    {
        $teacher = static::user()->isTeacher()
            ? static::user()
            : User::query()->where('role', UserRole::Teacher)->find($this->selection['teacher_id'] ?? null);

        return $teacher === null ? new Collection : TimetableSlot::query()->taughtBy($teacher)->get();
    }

    /**
     * @return array<int, string>
     */
    protected function teacherOptions(): array
    {
        return static::user()->isTeacher()
            ? []
            : User::query()->where('role', UserRole::Teacher)->orderBy('name')->pluck('name', 'id')->all();
    }

    protected static function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
