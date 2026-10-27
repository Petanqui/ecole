<?php

namespace App\Filament\Portail\Widgets;

use App\Models\Core\SchoolYear;
use App\Models\Core\Student;
use App\Models\Grades\Term;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * The children of the signed-in parent's family, with their class for the current school year
 * and their report cards for the terms that are closed.
 */
class MyChildren extends TableWidget
{
    protected static ?string $heading = 'Mes enfants';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $closedTerms = SchoolYear::current()->first()?->terms->where('is_closed', true) ?? collect();

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
            ])
            ->recordActions($closedTerms->map(fn (Term $term): Action => Action::make("reportCard{$term->number}")
                ->label("Bulletin {$term->name}")
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->visible(fn (Student $record): bool => $record->currentEnrollment !== null)
                ->url(fn (Student $record): string => route('filament.portail.report-cards.student', ['student' => $record, 'term' => $term]))
                ->openUrlInNewTab())
                ->values()
                ->all());
    }
}
