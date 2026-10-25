<?php

namespace App\Filament\Gestion\Resources\Core\Students\RelationManagers;

use App\Filament\Gestion\Resources\Core\Enrollments\Schemas\EnrollmentForm;
use App\Filament\Gestion\Resources\Core\Enrollments\Tables\EnrollmentsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'Inscriptions';

    protected static ?string $modelLabel = 'inscription';

    protected static ?string $pluralModelLabel = 'inscriptions';

    public function form(Schema $schema): Schema
    {
        return EnrollmentForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return EnrollmentsTable::configure($table);
    }
}
