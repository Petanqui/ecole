<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Billing\Payment;
use App\Services\Billing\StudentAccount;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ReceiptController extends Controller
{
    /**
     * The PDF receipt of a payment, with the account balance right after it.
     */
    public function __invoke(Payment $payment): Response
    {
        Gate::authorize('download-receipt', $payment);

        $payment->load(['enrollment.student', 'enrollment.schoolClass.schoolYear', 'receiver']);

        $account = new StudentAccount($payment->enrollment);
        $paidToDate = $account->payments
            ->filter(fn (Payment $other): bool => $other->paid_on->lt($payment->paid_on)
                || ($other->paid_on->eq($payment->paid_on) && $other->getKey() <= $payment->getKey()))
            ->sum('amount');

        return Pdf::loadView('billing.receipt', [
            'payment' => $payment,
            'total' => $account->total(),
            'paidToDate' => $paidToDate,
        ])
            ->setPaper('a5')
            ->stream("recu-{$payment->receipt_number}.pdf");
    }
}
