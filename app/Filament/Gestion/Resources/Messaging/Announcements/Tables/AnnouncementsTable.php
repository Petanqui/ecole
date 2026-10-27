<?php

namespace App\Filament\Gestion\Resources\Messaging\Announcements\Tables;

use App\Models\Messaging\Announcement;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['schoolClass', 'author'])->withCount('readers'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Publiée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Titre')
                    ->description(fn (Announcement $record): string => str($record->body)->limit(80))
                    ->searchable(['title', 'body'])
                    ->wrap(),
                TextColumn::make('audience_label')
                    ->label('Destinataires')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('author.name')
                    ->label('Auteur')
                    ->placeholder('—'),
                TextColumn::make('readers_count')
                    ->label('Lue par')
                    ->formatStateUsing(fn (int $state, Announcement $record): string => "{$state} / {$record->recipients()->count()} parent(s)"),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
