<?php

namespace App\Filament\Portail\Pages;

use App\Models\Messaging\Announcement;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

/**
 * The announcements addressed to the parent's children, newest first. Opening the page marks them as read.
 */
class Announcements extends Page
{
    protected string $view = 'filament.portail.pages.announcements';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Annonces';

    protected static ?string $slug = 'annonces';

    /**
     * The announcements that were still unread when the page was opened, to highlight them.
     *
     * @var array<int, int>
     */
    public array $newAnnouncementIds = [];

    public function mount(): void
    {
        $user = static::user();
        $unread = Announcement::query()->addressedTo($user)->unreadBy($user)->get();

        $this->newAnnouncementIds = $unread->modelKeys();
        $unread->each(fn (Announcement $announcement) => $announcement->markAsReadBy($user));
    }

    /**
     * @return Collection<int, Announcement>
     */
    public function getAnnouncements(): Collection
    {
        return Announcement::query()
            ->addressedTo(static::user())
            ->with(['schoolClass', 'author'])
            ->latest()
            ->limit(50)
            ->get();
    }

    public static function getNavigationBadge(): ?string
    {
        $user = static::user();
        $count = Announcement::query()->addressedTo($user)->unreadBy($user)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    protected static function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
