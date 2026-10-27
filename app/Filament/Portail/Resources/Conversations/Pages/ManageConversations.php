<?php

namespace App\Filament\Portail\Resources\Conversations\Pages;

use App\Filament\Portail\Resources\Conversations\ConversationResource;
use App\Models\Messaging\Conversation;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageConversations extends ManageRecords
{
    protected static string $resource = ConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Écrire à l\'école')
                ->modalHeading('Écrire à l\'école')
                ->modalSubmitActionLabel('Envoyer')
                ->createAnother(false)
                ->using(function (array $data): Conversation {
                    /** @var User $user */
                    $user = auth()->user();

                    $conversation = Conversation::create([
                        'family_id' => $user->guardian->family_id,
                        'student_id' => $data['student_id'] ?? null,
                        'teacher_id' => $data['recipient'] === 'administration' ? null : (int) $data['recipient'],
                        'subject' => $data['subject'],
                    ]);
                    $conversation->send($user, $data['body']);

                    return $conversation;
                })
                ->successNotificationTitle('Message envoyé')
                ->successRedirectUrl(fn (Conversation $record): string => ConversationResource::getUrl('view', ['record' => $record])),
        ];
    }
}
