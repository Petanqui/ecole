<?php

namespace App\Filament\Portail\Widgets;

use App\Models\Core\Student;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * The children of the signed-in parent's family, with their class for the current school year.
 */
class MyChildren extends TableWidget
{
    protected static ?string $heading = 'Mes enfants';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Student::query()
                ->whereRelation('family.guardians', 'user_id', auth()->id())
                ->with('currentEnrollment.schoolClass'))
            ->defaultSort('birth_date')
            ->paginated(false)
            ->emptyStateHeading('Aucun enfant rattaché à votre compte')
            ->emptyStateDescription('Contactez le secrétariat de l\'école.')
            ->columns([
                TextColumn::make('full_name')
                    ->label('Élève'),
                TextColumn::make('code')
                    ->label('Code'),
                TextColumn::make('currentEnrollment.schoolClass.name')
                    ->label('Classe')
                    ->placeholder('Non inscrit(e) cette année'),
                TextColumn::make('birth_date')
                    ->label('Né(e) le')
                    ->date('d/m/Y'),
            ]);
    }
}
