<?php

namespace App\Models\Messaging;

use App\Models\Core\Family;
use App\Models\Core\Student;
use App\Models\User;
use App\Policies\ConversationPolicy;
use Closure;
use Database\Factories\Messaging\ConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Messages between a family (every parent of it) and the school: the administration,
 * or one teacher when teacher_id is set. It may be about one of the children.
 */
#[Fillable(['family_id', 'student_id', 'teacher_id', 'subject'])]
#[UsePolicy(ConversationPolicy::class)]
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * @return HasMany<ConversationMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class);
    }

    /**
     * Who the family talks to: "Administration" or the teacher's name.
     *
     * @return Attribute<string, never>
     */
    protected function interlocutor(): Attribute
    {
        return Attribute::make(get: fn (): string => $this->teacher_id === null
            ? 'Administration'
            : ($this->teacher?->name ?? 'Enseignant(e)'));
    }

    /**
     * Add a message; its author has read the conversation up to it.
     */
    public function send(User $author, string $body): ConversationMessage
    {
        $message = $this->messages()->create(['author_id' => $author->getKey(), 'body' => $body]);
        $this->update(['last_message_at' => $message->created_at]);
        $this->markAsReadBy($author);

        return $message;
    }

    public function markAsReadBy(User $user): void
    {
        $lastMessageId = $this->messages()->max('id');

        if ($lastMessageId === null) {
            return;
        }

        DB::table('conversation_reads')->upsert(
            [['conversation_id' => $this->getKey(), 'user_id' => $user->getKey(), 'last_read_message_id' => $lastMessageId]],
            ['conversation_id', 'user_id'],
            ['last_read_message_id'],
        );
    }

    /**
     * Restricts a query on messages to those written by someone else and not read yet by the user.
     *
     * @return Closure(Builder<ConversationMessage>): void
     */
    public static function unreadMessagesFor(User $user): Closure
    {
        return fn (Builder $query) => $query
            ->where(fn (Builder $query) => $query
                ->whereNull('conversation_messages.author_id')
                ->orWhere('conversation_messages.author_id', '<>', $user->getKey()))
            ->whereRaw(
                'conversation_messages.id > coalesce((select last_read_message_id from conversation_reads where conversation_reads.conversation_id = conversation_messages.conversation_id and conversation_reads.user_id = ?), 0)',
                [$user->getKey()],
            );
    }

    /**
     * @param  Builder<Conversation>  $query
     */
    #[Scope]
    protected function unreadBy(Builder $query, User $user): void
    {
        $query->whereHas('messages', static::unreadMessagesFor($user));
    }

    /**
     * Staff see every conversation, a teacher their own, a parent those of their family.
     *
     * @param  Builder<Conversation>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        match (true) {
            $user->isStaffMember() => null,
            $user->isTeacher() => $query->where('teacher_id', $user->getKey()),
            default => $query->where('family_id', $user->guardian?->family_id),
        };
    }
}
