<?php

namespace App\Filament\Portail\Resources\Conversations\Pages;

use App\Filament\Gestion\Resources\Messaging\Conversations\Pages\ViewConversation as BaseViewConversation;
use App\Filament\Portail\Resources\Conversations\ConversationResource;

/**
 * The same conversation page as for the school, for the parents of the family.
 */
class ViewConversation extends BaseViewConversation
{
    protected static string $resource = ConversationResource::class;
}
