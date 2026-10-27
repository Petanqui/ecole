<?php

namespace App\Filament\Gestion\Resources\Billing\Payments\Pages;

use App\Filament\Gestion\Resources\Billing\Payments\PaymentResource;
use App\Models\Billing\Payment;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManagePayments extends ManageRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            static::recordPaymentAction(),
        ];
    }

    /**
     * Records a payment by the logged-in staff member, then offers to print its receipt.
     */
    public static function recordPaymentAction(): CreateAction
    {
        return CreateAction::make()
            ->label('Encaisser un paiement')
            ->modalHeading('Encaisser un paiement')
            ->createAnother(false)
            ->mutateDataUsing(fn (array $data): array => [...$data, 'received_by' => auth()->id()])
            ->successNotification(fn (Payment $record): Notification => Notification::make()
                ->success()
                ->title("Paiement enregistré — reçu {$record->receipt_number}")
                ->persistent()
                ->actions([
                    Action::make('printReceipt')
                        ->label('Imprimer le reçu')
                        ->button()
                        ->url(route('filament.gestion.receipts.show', $record), shouldOpenInNewTab: true),
                ]));
    }
}
