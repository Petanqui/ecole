<?php

namespace App\Filament\Gestion\Resources\Messaging\Conversations\Pages;

use App\Filament\Gestion\Resources\Messaging\Conversations\ConversationResource;
use App\Models\Messaging\Conversation;
use App\Models\Messaging\ConversationMessage;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;

/**
 * A conversation between a family and the school: its messages, and the reply box. Opening it marks it as read.
 * The parent portal uses it too, with its own resource.
 *
 * @property-read Schema $form
 */
class ViewConversation extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ConversationResource::class;

    protected string $view = 'filament.messaging.view-conversation';

    /**
     * @var array{body?: string|null}
     */
    public array $reply = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(static::user()->can('view', $this->record), 403);

        $this->getConversation()->markAsReadBy(static::user());
        $this->form->fill();
    }

    public function getTitle(): string|Htmlable
    {
        return $this->getConversation()->subject;
    }

    public function getSubheading(): string|Htmlable|null
    {
        $conversation = $this->getConversation();

        return collect([
            "Famille {$conversation->family->name}",
            $conversation->student ? "au sujet de {$conversation->student->full_name}" : null,
            "avec : {$conversation->interlocutor}",
        ])->filter()->join(' — ');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('reply')
            ->components([
                Textarea::make('body')
                    ->label('Votre réponse')
                    ->rows(4)
                    ->required()
                    ->maxLength(5000),
            ]);
    }

    public function send(): void
    {
        $data = $this->form->getState();

        $this->getConversation()->send(static::user(), $data['body']);
        $this->form->fill();

        Notification::make()->success()->title('Message envoyé')->send();
    }

    /**
     * @return Collection<int, ConversationMessage>
     */
    public function getThreadMessages(): Collection
    {
        return $this->getConversation()->messages()->with('author')->orderBy('id')->get();
    }

    public function getViewer(): User
    {
        return static::user();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Retour aux messages')
                ->color('gray')
                ->url(static::getResource()::getUrl()),
        ];
    }

    protected function getConversation(): Conversation
    {
        /** @var Conversation */
        return $this->getRecord();
    }

    protected static function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
