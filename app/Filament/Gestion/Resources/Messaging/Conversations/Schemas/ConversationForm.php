<?php

namespace App\Filament\Gestion\Resources\Messaging\Conversations\Schemas;

use App\Models\Core\Enrollment;
use App\Models\Core\Student;
use App\Models\User;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

/**
 * A new conversation from the school with the family of a student, and its first message.
 */
class ConversationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('student_id')
                    ->label('Élève (sa famille recevra le message)')
                    ->searchable()
                    ->options(fn (): array => static::studentOptions(''))
                    ->getSearchResultsUsing(fn (string $search): array => static::studentOptions($search))
                    ->getOptionLabelUsing(fn (mixed $value): ?string => Student::find($value)?->full_name)
                    ->required()
                    ->rules([
                        fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            /** @var User $user */
                            $user = auth()->user();

                            if (! static::reachableEnrollments($user)->where('student_id', $value)->exists()) {
                                $fail('Vous ne pouvez pas écrire à la famille de cet élève.');
                            }
                        },
                    ]),
                TextInput::make('subject')
                    ->label('Objet')
                    ->required()
                    ->maxLength(255),
                Textarea::make('body')
                    ->label('Message')
                    ->rows(6)
                    ->required()
                    ->maxLength(5000),
            ]);
    }

    /**
     * Students enrolled this year matching the search, limited to the classes they teach for a teacher.
     *
     * @return array<int, string>
     */
    protected static function studentOptions(string $search): array
    {
        /** @var User $user */
        $user = auth()->user();

        return static::reachableEnrollments($user)
            ->matchingStudent($search)
            ->with(['student', 'schoolClass'])
            ->orderBy(Student::select('last_name')->whereColumn('students.id', 'enrollments.student_id'))
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Enrollment $enrollment): array => [
                $enrollment->student_id => "{$enrollment->student->full_name} ({$enrollment->student->code}) — {$enrollment->schoolClass->name}",
            ])
            ->all();
    }

    /**
     * The current-year enrollments whose family the user may write to: all for staff,
     * those of the classes they teach for a teacher.
     *
     * @return Builder<Enrollment>
     */
    public static function reachableEnrollments(User $user): Builder
    {
        return Enrollment::query()
            ->whereRelation('schoolYear', 'is_current', true)
            ->when(! $user->isStaffMember(), fn (Builder $query) => $query->whereHas(
                'schoolClass.classSubjects',
                fn (Builder $query) => $query->where('teacher_id', $user->getKey()),
            ));
    }
}
