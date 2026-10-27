<?php

namespace App\Filament\Gestion\Resources\Core\Enrollments\RelationManagers;

use App\Filament\Gestion\Resources\Billing\Payments\Pages\ManagePayments;
use App\Filament\Gestion\Resources\Billing\Payments\Schemas\PaymentForm;
use App\Filament\Gestion\Resources\Billing\Payments\Tables\PaymentsTable;
use App\Models\Core\Enrollment;
use App\Services\Billing\StudentAccount;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * The account of a student for the year: what they owe, what they paid, and the payment desk.
 */
class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Compte';

    protected static ?string $modelLabel = 'paiement';

    protected static ?string $pluralModelLabel = 'paiements';

    public function form(Schema $schema): Schema
    {
        return PaymentForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return PaymentsTable::configure($table, showsStudent: false)
            ->heading('Paiements')
            ->description(fn (): string => $this->summary())
            ->headerActions([
                Action::make('dues')
                    ->label('Échéancier')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->color('gray')
                    ->modalHeading('Échéancier de l\'année')
                    ->modalContent(fn () => view('filament.gestion.billing.dues', ['account' => $this->account()]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer'),
                ManagePayments::recordPaymentAction(),
            ]);
    }

    protected function account(): StudentAccount
    {
        /** @var Enrollment $enrollment */
        $enrollment = $this->getOwnerRecord();

        return new StudentAccount($enrollment);
    }

    protected function summary(): string
    {
        $account = $this->account();

        return collect([
            'Total de l\'année : '.StudentAccount::formatAmount($account->total()),
            'Payé : '.StudentAccount::formatAmount($account->paid()),
            'Reste : '.StudentAccount::formatAmount($account->balance()),
            'En retard : '.StudentAccount::formatAmount($account->overdue(now())),
        ])->join(' — ');
    }
}
