<?php

namespace App\Providers;

use App\Models\Billing\Payment;
use App\Models\Core\Student;
use App\Models\Grades\Term;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->defineReportCardAbilities();
        $this->defineBillingAbilities();
    }

    /**
     * Staff record payments; only administrators cancel one. A parent downloads the receipts
     * of their own children.
     */
    protected function defineBillingAbilities(): void
    {
        Gate::define('cancel-payment', fn (User $user, Payment $payment): bool => $user->isAdmin() && ! $payment->isCancelled());

        Gate::define('download-receipt', function (User $user, Payment $payment): bool {
            if ($user->isStaffMember()) {
                return true;
            }

            return $payment->enrollment->student->family->guardians()->where('user_id', $user->getKey())->exists();
        });
    }

    /**
     * Staff print report cards for whole classes. A parent downloads the report cards of their
     * own children, once the term is closed (i.e. the grades are final).
     */
    protected function defineReportCardAbilities(): void
    {
        Gate::define('print-class-report-cards', fn (User $user): bool => $user->isStaffMember());

        Gate::define('download-report-card', function (User $user, Student $student, Term $term): bool {
            if ($user->isStaffMember()) {
                return true;
            }

            return $term->is_closed
                && $student->family->guardians()->where('user_id', $user->getKey())->exists();
        });
    }
}
