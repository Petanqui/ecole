<?php

namespace App\Filament\Gestion\Resources\Messaging\Conversations\Pages;

use App\Filament\Gestion\Resources\Messaging\Conversations\ConversationResource;
use App\Models\Core\Student;
use App\Models\Messaging\Conversation;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageConversations extends ManageRecords
{
    protected static string $resource = ConversationResource::class;

    /**
     * A teacher writes in their own name, staff on behalf of the administration.
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nouveau message')
                ->modalHeading('Écrire à une famille')
                ->modalSubmitActionLabel('Envoyer')
                ->createAnother(false)
                ->using(function (array $data): Conversation {
                    /** @var User $user */
                    $user = auth()->user();

                    $conversation = Conversation::create([
                        'family_id' => Student::findOrFail($data['student_id'])->family_id,
                        'student_id' => $data['student_id'],
                        'teacher_id' => $user->isTeacher() ? $user->getKey() : null,
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
