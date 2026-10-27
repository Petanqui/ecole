<?php

namespace App\Policies;

use App\Models\Messaging\Conversation;
use App\Models\User;

/**
 * Staff read and answer every conversation; a teacher those addressed to them;
 * a parent those of their family.
 */
class ConversationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return match (true) {
            $user->isStaffMember() => true,
            $user->isTeacher() => $conversation->teacher_id === $user->getKey(),
            default => $user->guardian !== null && $conversation->family_id === $user->guardian->family_id,
        };
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Writing in a conversation is open to everyone who can read it.
     */
    public function update(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation);
    }

    /**
     * Messages stay: a conversation is never deleted.
     */
    public function delete(User $user, Conversation $conversation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete several models at once.
     */
    public function deleteAny(User $user): bool
    {
        return false;
    }
}
