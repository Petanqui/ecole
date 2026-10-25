<?php

namespace App\Filament\Gestion\Resources\Core\Families\RelationManagers;

use App\Filament\Gestion\Resources\Core\Students\Schemas\StudentForm;
use App\Filament\Gestion\Resources\Core\Students\Tables\StudentsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class StudentsRelationManager extends RelationManager
{
    protected static string $relationship = 'students';

    protected static ?string $title = 'Enfants';

    protected static ?string $modelLabel = 'élève';

    protected static ?string $pluralModelLabel = 'élèves';

    public function form(Schema $schema): Schema
    {
        return StudentForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return StudentsTable::configure($table);
    }
}
