<?php

namespace App\Filament\Gestion\Resources\Messaging\Conversations\Tables;

use App\Filament\Gestion\Resources\Messaging\Conversations\ConversationResource;
use App\Models\Messaging\Conversation;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The inbox: latest conversations first, unread ones flagged.
 */
class ConversationsTable
{
    public static function configure(Table $table): Table
    {
        $user = static::user();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['family', 'student.currentEnrollment.schoolClass', 'teacher'])
                ->withCount(['messages', 'messages as unread_messages_count' => Conversation::unreadMessagesFor($user)]))
            ->defaultSort(fn (Builder $query) => $query->orderByDesc('last_message_at')->orderByDesc('id'))
            ->recordUrl(fn (Conversation $record): string => ConversationResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('last_message_at')
                    ->label('Dernier message')
                    ->since()
                    ->dateTimeTooltip('d/m/Y H:i'),
                TextColumn::make('subject')
                    ->label('Objet')
                    ->weight(fn (Conversation $record): ?string => $record->unread_messages_count > 0 ? 'bold' : null)
                    ->description(fn (Conversation $record): ?string => $record->student
                        ? $record->student->full_name.($record->student->currentEnrollment ? " — {$record->student->currentEnrollment->schoolClass->name}" : '')
                        : null)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('family.name')
                    ->label('Famille')
                    ->searchable(),
                TextColumn::make('interlocutor')
                    ->label('Avec')
                    ->visible($user->isStaffMember()),
                TextColumn::make('messages_count')
                    ->label('Messages')
                    ->alignCenter(),
                TextColumn::make('unread_messages_count')
                    ->label('Non lus')
                    ->alignCenter()
                    ->badge()
                    ->color('danger')
                    ->formatStateUsing(fn (int $state): ?string => $state > 0 ? (string) $state : null)
                    ->placeholder('—'),
            ])
            ->filters([
                TernaryFilter::make('unread')
                    ->label('Lecture')
                    ->placeholder('Toutes')
                    ->trueLabel('Non lues')
                    ->falseLabel('Lues')
                    ->queries(
                        true: fn (Builder $query) => $query->unreadBy($user),
                        false: fn (Builder $query) => $query->whereDoesntHave('messages', Conversation::unreadMessagesFor($user)),
                    ),
                SelectFilter::make('interlocutor')
                    ->label('Avec')
                    ->options(['administration' => 'Administration', 'teachers' => 'Enseignants'])
                    ->query(fn (Builder $query, array $data) => match ($data['value']) {
                        'administration' => $query->whereNull('teacher_id'),
                        'teachers' => $query->whereNotNull('teacher_id'),
                        default => $query,
                    })
                    ->visible($user->isStaffMember()),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Ouvrir')
                    ->icon(Heroicon::OutlinedEnvelopeOpen)
                    ->url(fn (Conversation $record): string => ConversationResource::getUrl('view', ['record' => $record])),
            ]);
    }

    protected static function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
