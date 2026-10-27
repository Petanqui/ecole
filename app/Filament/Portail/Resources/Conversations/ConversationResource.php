<?php

namespace App\Filament\Portail\Resources\Conversations;

use App\Filament\Portail\Resources\Conversations\Pages\ManageConversations;
use App\Filament\Portail\Resources\Conversations\Pages\ViewConversation;
use App\Models\Core\Student;
use App\Models\Grades\ClassSubject;
use App\Models\Messaging\Conversation;
use App\Models\User;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * The conversations of the parent's family with the school.
 */
class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Messages';

    protected static ?string $modelLabel = 'conversation';

    protected static ?string $pluralModelLabel = 'messages';

    protected static ?string $slug = 'messages';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(static::user());
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->unreadBy(static::user())->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    /**
     * A new message to the administration or to a teacher of one of the children.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('recipient')
                    ->label('Destinataire')
                    ->options(fn (): array => static::recipientOptions())
                    ->default('administration')
                    ->selectablePlaceholder(false)
                    ->required()
                    ->rules([
                        fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            if (! array_key_exists((string) $value, static::recipientOptions())) {
                                $fail('Choisissez un destinataire de la liste.');
                            }
                        },
                    ]),
                Select::make('student_id')
                    ->label('Au sujet de')
                    ->placeholder('Toute la famille')
                    ->options(fn (): array => static::childOptions())
                    ->in(fn (): array => array_keys(static::childOptions())),
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

    public static function table(Table $table): Table
    {
        $user = static::user();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['student', 'teacher'])
                ->withCount(['messages as unread_messages_count' => Conversation::unreadMessagesFor($user)]))
            ->defaultSort(fn (Builder $query) => $query->orderByDesc('last_message_at')->orderByDesc('id'))
            ->recordUrl(fn (Conversation $record): string => static::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('Aucun message')
            ->emptyStateDescription('Écrivez à l\'administration ou à un enseignant de votre enfant.')
            ->columns([
                TextColumn::make('last_message_at')
                    ->label('Dernier message')
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('subject')
                    ->label('Objet')
                    ->weight(fn (Conversation $record): ?string => $record->unread_messages_count > 0 ? 'bold' : null)
                    ->description(fn (Conversation $record): ?string => $record->student?->full_name)
                    ->wrap(),
                TextColumn::make('interlocutor')
                    ->label('Avec'),
                TextColumn::make('unread_messages_count')
                    ->label('Non lus')
                    ->badge()
                    ->color('danger')
                    ->formatStateUsing(fn (int $state): ?string => $state > 0 ? (string) $state : null)
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Ouvrir')
                    ->icon(Heroicon::OutlinedEnvelopeOpen)
                    ->url(fn (Conversation $record): string => static::getUrl('view', ['record' => $record])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageConversations::route('/'),
            'view' => ViewConversation::route('/{record}'),
        ];
    }

    /**
     * "Administration", then the teachers of the children's classes this year: "Jean Kouassi — Mathématiques (6e1)".
     *
     * @return array<string, string>
     */
    public static function recipientOptions(): array
    {
        $classIds = static::children()->pluck('currentEnrollment.school_class_id')->filter();

        $teachers = ClassSubject::query()
            ->whereIn('school_class_id', $classIds)
            ->whereNotNull('teacher_id')
            ->with(['teacher', 'subject', 'schoolClass'])
            ->get()
            ->groupBy('teacher_id')
            ->mapWithKeys(fn ($classSubjects, int $teacherId): array => [
                (string) $teacherId => $classSubjects->first()->teacher->name.' — '
                    .$classSubjects->map(fn (ClassSubject $classSubject): string => "{$classSubject->subject->name} ({$classSubject->schoolClass->name})")->unique()->join(', '),
            ])
            ->sort();

        return ['administration' => 'Administration'] + $teachers->all();
    }

    /**
     * @return array<int, string>
     */
    protected static function childOptions(): array
    {
        return static::children()->pluck('full_name', 'id')->all();
    }

    /**
     * @return Collection<int, Student>
     */
    protected static function children(): Collection
    {
        return static::user()->guardian?->family->students()->with('currentEnrollment')->orderBy('first_name')->get()
            ?? new Collection;
    }

    protected static function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
