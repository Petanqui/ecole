<?php

namespace App\Models\Messaging;

use App\Enums\Core\UserRole;
use App\Models\User;
use Database\Factories\Messaging\ConversationMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['conversation_id', 'author_id', 'body'])]
class ConversationMessage extends Model
{
    /** @use HasFactory<ConversationMessageFactory> */
    use HasFactory;

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
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * "Awa Koné (parent)", "Marie Yao — Administration", "Jean Kouassi — Enseignant(e)".
     *
     * @return Attribute<string, never>
     */
    protected function authorLabel(): Attribute
    {
        return Attribute::make(get: fn (): string => match ($this->author?->role) {
            null => 'Compte supprimé',
            UserRole::Guardian => "{$this->author->name} (parent)",
            UserRole::Teacher => "{$this->author->name} — Enseignant(e)",
            default => "{$this->author->name} — Administration",
        });
    }

    public function isFromSchool(): bool
    {
        return $this->author !== null && $this->author->role !== UserRole::Guardian;
    }
}
