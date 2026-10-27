<?php

namespace App\Filament\Gestion\Resources\Messaging\Announcements\Schemas;

use App\Enums\Core\GradeLevel;
use App\Enums\Messaging\AnnouncementAudience;
use App\Models\Core\SchoolClass;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                ToggleButtons::make('audience')
                    ->label('Destinataires')
                    ->options(fn (): array => static::audienceOptions())
                    ->default(fn (): AnnouncementAudience => static::user()->isStaffMember() ? AnnouncementAudience::School : AnnouncementAudience::SchoolClass)
                    ->inline()
                    ->required()
                    ->live()
                    ->columnSpanFull(),
                Select::make('level')
                    ->label('Niveau')
                    ->options(GradeLevel::class)
                    ->visible(fn (Get $get): bool => static::audience($get) === AnnouncementAudience::Level)
                    ->required(),
                Select::make('school_class_id')
                    ->label('Classe')
                    ->options(fn (): array => static::classOptions())
                    ->searchable()
                    ->visible(fn (Get $get): bool => static::audience($get) === AnnouncementAudience::SchoolClass)
                    ->required(),
                TextInput::make('title')
                    ->label('Titre')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('body')
                    ->label('Message')
                    ->rows(8)
                    ->required()
                    ->maxLength(5000)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * A teacher only writes to the parents of a class they teach.
     *
     * @return array<string, string>
     */
    protected static function audienceOptions(): array
    {
        $audiences = static::user()->isStaffMember() ? AnnouncementAudience::cases() : [AnnouncementAudience::SchoolClass];

        return collect($audiences)->mapWithKeys(fn (AnnouncementAudience $audience): array => [$audience->value => $audience->getLabel()])->all();
    }

    /**
     * Current-year classes, limited to the classes they teach for a teacher.
     *
     * @return array<int, string>
     */
    protected static function classOptions(): array
    {
        $user = static::user();

        return SchoolClass::query()
            ->whereRelation('schoolYear', 'is_current', true)
            ->when(! $user->isStaffMember(), fn (Builder $query) => $query->whereHas('classSubjects', fn (Builder $query) => $query->where('teacher_id', $user->getKey())))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    protected static function audience(Get $get): ?AnnouncementAudience
    {
        $audience = $get('audience');

        return $audience instanceof AnnouncementAudience ? $audience : AnnouncementAudience::tryFrom((string) $audience);
    }

    protected static function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
