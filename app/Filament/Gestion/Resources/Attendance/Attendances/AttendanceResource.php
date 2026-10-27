<?php

namespace App\Filament\Gestion\Resources\Attendance\Attendances;

use App\Filament\Gestion\Concerns\RestrictedToStaffMembers;
use App\Filament\Gestion\Resources\Attendance\Attendances\Pages\ManageAttendances;
use App\Filament\Gestion\Resources\Attendance\Attendances\Tables\AttendancesTable;
use App\Models\Attendance\Attendance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Absences and late arrivals are noted during roll calls; here staff follow them up and justify them.
 */
class AttendanceResource extends Resource
{
    use RestrictedToStaffMembers;

    protected static ?string $model = Attendance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserMinus;

    protected static string|UnitEnum|null $navigationGroup = 'Présences';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Absences et retards';

    protected static ?string $modelLabel = 'absence';

    protected static ?string $pluralModelLabel = 'absences et retards';

    public static function table(Table $table): Table
    {
        return AttendancesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAttendances::route('/'),
        ];
    }
}
