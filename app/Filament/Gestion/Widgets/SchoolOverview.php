<?php

namespace App\Filament\Gestion\Widgets;

use App\Enums\Attendance\AttendanceStatus;
use App\Filament\Gestion\Concerns\VisibleToStaffMembers;
use App\Filament\Gestion\Pages\Billing\ArrearsPage;
use App\Filament\Gestion\Resources\Attendance\Attendances\AttendanceResource;
use App\Filament\Gestion\Resources\Billing\Payments\PaymentResource;
use App\Models\Attendance\Attendance;
use App\Models\Billing\Payment;
use App\Models\Core\Enrollment;
use App\Models\Core\SchoolYear;
use App\Services\Billing\StudentAccount;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/**
 * The headline figures of the current school year.
 */
class SchoolOverview extends StatsOverviewWidget
{
    use VisibleToStaffMembers;

    protected static ?int $sort = 1;

    protected ?string $heading = 'L\'école en chiffres';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $schoolYear = SchoolYear::current()->first();

        if ($schoolYear === null) {
            return [Stat::make('Année scolaire', 'Aucune année en cours')];
        }

        $enrollments = Enrollment::query()->where('school_year_id', $schoolYear->getKey())->get();
        $accounts = StudentAccount::forEnrollments($enrollments);
        $overdue = $accounts->map(fn (StudentAccount $account): int => $account->overdue(now()));

        $payments = Payment::query()->valid()->whereIn('enrollment_id', $enrollments->modelKeys());
        $paidThisMonth = (int) (clone $payments)->whereBetween('paid_on', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount');

        $recentAbsences = Attendance::query()
            ->where('status', AttendanceStatus::Absent)
            ->whereHas('session', fn (Builder $query) => $query->whereDate('held_on', '>=', now()->subDays(30)))
            ->with('session')
            ->get();
        $unjustifiedHours = $recentAbsences->reject->isJustified()->sum(fn (Attendance $attendance): int => $attendance->missedHours());

        return [
            Stat::make('Élèves inscrits', $enrollments->count())
                ->description($enrollments->pluck('school_class_id')->unique()->count().' classe(s) — '.$schoolYear->name)
                ->icon(Heroicon::OutlinedUserGroup),
            Stat::make('Encaissé ce mois-ci', StudentAccount::formatAmount($paidThisMonth))
                ->description('Sur l\'année : '.StudentAccount::formatAmount((int) $payments->sum('amount')))
                ->icon(Heroicon::OutlinedBanknotes)
                ->url(PaymentResource::getUrl()),
            Stat::make('Paiements en retard', StudentAccount::formatAmount($overdue->sum()))
                ->description($overdue->filter()->count().' élève(s) concerné(s)')
                ->color($overdue->sum() > 0 ? 'danger' : 'success')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->url(ArrearsPage::getUrl()),
            Stat::make('Absences (30 derniers jours)', $recentAbsences->sum(fn (Attendance $attendance): int => $attendance->missedHours()).' h')
                ->description("dont {$unjustifiedHours} h non justifiée(s)")
                ->color($unjustifiedHours > 0 ? 'warning' : 'success')
                ->icon(Heroicon::OutlinedUserMinus)
                ->url(AttendanceResource::getUrl()),
        ];
    }
}
