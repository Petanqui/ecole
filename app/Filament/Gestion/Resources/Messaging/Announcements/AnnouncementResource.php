<?php

namespace App\Filament\Gestion\Resources\Messaging\Announcements;

use App\Filament\Gestion\Resources\Messaging\Announcements\Pages\ManageAnnouncements;
use App\Filament\Gestion\Resources\Messaging\Announcements\Schemas\AnnouncementForm;
use App\Filament\Gestion\Resources\Messaging\Announcements\Tables\AnnouncementsTable;
use App\Models\Messaging\Announcement;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Messagerie';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Annonces';

    protected static ?string $modelLabel = 'annonce';

    protected static ?string $pluralModelLabel = 'annonces';

    /**
     * Teachers only ever see their own announcements.
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var User $user */
        $user = auth()->user();

        return parent::getEloquentQuery()->visibleTo($user);
    }

    public static function form(Schema $schema): Schema
    {
        return AnnouncementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnnouncementsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAnnouncements::route('/'),
        ];
    }
}
