<?php

namespace App\Filament\Gestion\Resources\Messaging\Conversations;

use App\Filament\Gestion\Resources\Messaging\Conversations\Pages\ManageConversations;
use App\Filament\Gestion\Resources\Messaging\Conversations\Pages\ViewConversation;
use App\Filament\Gestion\Resources\Messaging\Conversations\Schemas\ConversationForm;
use App\Filament\Gestion\Resources\Messaging\Conversations\Tables\ConversationsTable;
use App\Models\Messaging\Conversation;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Messagerie';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Messages';

    protected static ?string $modelLabel = 'conversation';

    protected static ?string $pluralModelLabel = 'messages';

    /**
     * Teachers only ever see the conversations addressed to them.
     */
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

    public static function form(Schema $schema): Schema
    {
        return ConversationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConversationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageConversations::route('/'),
            'view' => ViewConversation::route('/{record}'),
        ];
    }

    protected static function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
