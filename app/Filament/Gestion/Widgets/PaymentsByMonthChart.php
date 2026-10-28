<?php

namespace App\Filament\Gestion\Widgets;

use App\Filament\Gestion\Concerns\VisibleToStaffMembers;
use App\Models\Billing\Payment;
use App\Models\Core\SchoolYear;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;

/**
 * Money received each month of the current school year (cancelled payments excluded).
 */
class PaymentsByMonthChart extends ChartWidget
{
    use VisibleToStaffMembers;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Encaissements par mois (FCFA)';

    protected ?string $maxHeight = '260px';

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $schoolYear = SchoolYear::current()->first();

        if ($schoolYear === null) {
            return ['datasets' => [], 'labels' => []];
        }

        $payments = Payment::query()
            ->valid()
            ->whereRelation('enrollment', 'school_year_id', $schoolYear->getKey())
            ->get(['amount', 'paid_on'])
            ->groupBy(fn (Payment $payment): string => $payment->paid_on->format('Y-m'));

        $months = collect(CarbonPeriod::create($schoolYear->starts_on->copy()->startOfMonth(), '1 month', $schoolYear->ends_on->copy()->startOfMonth()));

        return [
            'datasets' => [[
                'label' => 'Encaissé',
                'data' => $months->map(fn ($month): int => (int) $payments->get($month->format('Y-m'), collect())->sum('amount'))->all(),
                'backgroundColor' => 'rgb(217, 119, 6)',
                'borderRadius' => 4,
                'maxBarThickness' => 32,
            ]],
            'labels' => $months->map(fn ($month): string => ucfirst($month->translatedFormat('M Y')))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true]],
        ];
    }
}
