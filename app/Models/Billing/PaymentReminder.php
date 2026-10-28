<?php

namespace App\Models\Billing;

use App\Models\Core\Enrollment;
use App\Models\Messaging\Conversation;
use App\Models\User;
use App\Services\Billing\StudentAccount;
use Carbon\CarbonInterface;
use Database\Factories\Billing\PaymentReminderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * A portal message sent to a family about the overdue payments of one of their children.
 * The reminders of an enrollment follow one another in the same conversation.
 */
#[Fillable(['enrollment_id', 'conversation_id', 'sent_by', 'overdue', 'as_of'])]
class PaymentReminder extends Model
{
    /** @use HasFactory<PaymentReminderFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'overdue' => 'integer',
            'as_of' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Enrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Write to the student's family on behalf of the administration, in the conversation
     * of the previous reminders when there is one.
     */
    public static function send(StudentAccount $account, User $author, CarbonInterface $asOf, ?string $body = null): self
    {
        $enrollment = $account->enrollment;
        $student = $enrollment->student;

        return DB::transaction(function () use ($account, $author, $asOf, $body, $enrollment, $student): self {
            $conversation = static::query()
                ->where('enrollment_id', $enrollment->getKey())
                ->whereRelation('conversation', 'family_id', $student->family_id)
                ->latest('id')
                ->first()
                ?->conversation
                ?? Conversation::create([
                    'family_id' => $student->family_id,
                    'student_id' => $student->getKey(),
                    'teacher_id' => null,
                    'subject' => "Paiements de {$student->full_name}",
                ]);

            $conversation->send($author, $body ?? static::defaultMessage($account, $asOf));

            return static::create([
                'enrollment_id' => $enrollment->getKey(),
                'conversation_id' => $conversation->getKey(),
                'sent_by' => $author->getKey(),
                'overdue' => $account->overdue($asOf),
                'as_of' => $asOf,
            ]);
        });
    }

    /**
     * The reminder text: the overdue installments, what they add up to and what is left for the year.
     */
    public static function defaultMessage(StudentAccount $account, CarbonInterface $asOf): string
    {
        $enrollment = $account->enrollment;

        $lines = $account->dues
            ->filter(fn (array $due): bool => $due['due_on']->lte($asOf) && $due['remaining'] > 0)
            ->map(fn (array $due): string => "- {$due['label']} (échéance du {$due['due_on']->format('d/m/Y')}) : "
                .StudentAccount::formatAmount($due['remaining']))
            ->implode("\n");

        return "Bonjour,\n\n"
            ."Sauf erreur de notre part, au {$asOf->format('d/m/Y')}, il reste à régler pour {$enrollment->student->full_name} "
            ."({$enrollment->schoolClass->name}) un retard de ".StudentAccount::formatAmount($account->overdue($asOf))." :\n"
            .$lines."\n\n"
            .'Reste à payer sur l\'année : '.StudentAccount::formatAmount($account->balance()).".\n\n"
            .'Merci de bien vouloir régulariser auprès du secrétariat (espèces, Mobile Money, virement ou chèque). '
            ."Si le paiement a déjà été fait, merci de ne pas tenir compte de ce message.\n\n"
            ."Cordialement,\nL'administration";
    }
}
