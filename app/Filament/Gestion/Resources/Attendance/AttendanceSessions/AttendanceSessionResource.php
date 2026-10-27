<?php

namespace App\Filament\Gestion\Resources\Attendance\AttendanceSessions;

use App\Filament\Gestion\Resources\Attendance\AttendanceSessions\Pages\ManageAttendanceSessions;
use App\Filament\Gestion\Resources\Attendance\AttendanceSessions\Pages\TakeAttendance;
use App\Filament\Gestion\Resources\Attendance\AttendanceSessions\Schemas\AttendanceSessionForm;
use App\Filament\Gestion\Resources\Attendance\AttendanceSessions\Tables\AttendanceSessionsTable;
use App\Models\Attendance\AttendanceSession;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AttendanceSessionResource extends Resource
{
    protected static ?string $model = AttendanceSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Présences';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Appels';

    protected static ?string $modelLabel = 'appel';

    protected static ?string $pluralModelLabel = 'appels';

    /**
     * Teachers only ever see the roll calls of the subjects they teach.
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var User $user */
        $user = auth()->user();

        return parent::getEloquentQuery()->visibleTo($user);
    }

    public static function form(Schema $schema): Schema
    {
        return AttendanceSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceSessionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAttendanceSessions::route('/'),
            'take' => TakeAttendance::route('/{record}'),
        ];
    }
}
