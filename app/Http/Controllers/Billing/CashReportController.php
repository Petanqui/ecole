<?php

namespace App\Http\Controllers\Billing;

use App\Enums\Billing\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Billing\CashReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CashReportController extends Controller
{
    /**
     * The cash report of a period as a PDF (A4), to sign when the cash desk is closed.
     */
    public function __invoke(Request $request): Response
    {
        Gate::authorize('view-cash-report');

        $validated = $request->validate([
            'du' => ['required', 'date'],
            'au' => ['required', 'date', 'after_or_equal:du'],
            'caissier' => ['nullable', 'integer'],
            'mode' => ['nullable', Rule::enum(PaymentMethod::class)],
        ]);

        $report = new CashReport(
            from: Carbon::parse($validated['du']),
            until: Carbon::parse($validated['au']),
            receiverId: isset($validated['caissier']) ? (int) $validated['caissier'] : null,
            method: PaymentMethod::tryFrom($validated['mode'] ?? ''),
        );

        return Pdf::loadView('billing.cash-report', [
            'report' => $report,
            'cashierName' => $report->receiverId === null ? null : User::find($report->receiverId)?->name,
        ])
            ->setPaper('a4')
            ->stream('etat-de-caisse-'.$report->from->format('Y-m-d').'-'.$report->until->format('Y-m-d').'.pdf');
    }
}
