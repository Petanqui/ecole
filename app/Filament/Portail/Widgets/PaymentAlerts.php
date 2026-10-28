<?php

namespace App\Filament\Portail\Widgets;

use App\Filament\Portail\Resources\Conversations\ConversationResource;
use App\Models\Billing\PaymentReminder;
use App\Models\Core\Student;
use App\Services\Billing\StudentAccount;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

/**
 * Tells the parent, at the top of the portal, which children are behind with their payments
 * and which installments fall due soon. Hidden when there is nothing to say.
 */
class PaymentAlerts extends Widget
{
    /**
     * An installment due within this many days is announced.
     */
    public const int UPCOMING_DAYS = 14;

    protected string $view = 'filament.portail.widgets.payment-alerts';

    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return static::alerts()->isNotEmpty();
    }

    /**
     * @return array{alerts: Collection<int, array{student: string, overdue: int, overdue_since: ?string, upcoming: ?string, conversation_url: ?string}>}
     */
    protected function getViewData(): array
    {
        return ['alerts' => static::alerts()];
    }

    /**
     * One alert per child of the parent's family who is behind or has an installment due soon.
     *
     * @return Collection<int, array{student: string, overdue: int, overdue_since: ?string, upcoming: ?string, conversation_url: ?string}>
     */
    public static function alerts(): Collection
    {
        $today = today();

        return Student::query()
            ->whereRelation('family.guardians', 'user_id', auth()->id())
            ->whereHas('currentEnrollment')
            ->with(['currentEnrollment.schoolClass'])
            ->orderBy('birth_date')
            ->get()
            ->map(function (Student $student) use ($today): ?array {
                $account = new StudentAccount($student->currentEnrollment);
                $overdue = $account->overdue($today);
                $firstOverdue = $account->dues->first(fn (array $due): bool => $due['due_on']->lte($today) && $due['remaining'] > 0);
                $next = $account->dues->first(fn (array $due): bool => $due['due_on']->gt($today) && $due['remaining'] > 0);
                $isNextSoon = $next !== null && $next['due_on']->lte($today->copy()->addDays(static::UPCOMING_DAYS));

                if ($overdue === 0 && ! $isNextSoon) {
                    return null;
                }

                $reminder = PaymentReminder::query()
                    ->where('enrollment_id', $student->currentEnrollment->getKey())
                    ->whereRelation('conversation', 'family_id', $student->family_id)
                    ->latest('id')
                    ->first();

                return [
                    'student' => $student->full_name,
                    'overdue' => $overdue,
                    'overdue_since' => $overdue > 0 ? $firstOverdue['due_on']->format('d/m/Y') : null,
                    'upcoming' => $isNextSoon
                        ? StudentAccount::formatAmount($next['remaining']).' à payer avant le '.$next['due_on']->format('d/m/Y')." ({$next['label']})"
                        : null,
                    'conversation_url' => $overdue > 0 && $reminder !== null
                        ? ConversationResource::getUrl('view', ['record' => $reminder->conversation_id])
                        : null,
                ];
            })
            ->filter()
            ->values();
    }
}
