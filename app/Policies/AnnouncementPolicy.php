<?php

namespace App\Policies;

use App\Models\Messaging\Announcement;
use App\Models\User;

/**
 * Staff manage every announcement; a teacher writes to the parents of their classes and manages
 * their own announcements.
 */
class AnnouncementPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaffMember() || $user->isTeacher();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Announcement $announcement): bool
    {
        return $user->isStaffMember() || $this->wrote($user, $announcement);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isStaffMember() || $user->isTeacher();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Announcement $announcement): bool
    {
        return $user->isStaffMember() || $this->wrote($user, $announcement);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Announcement $announcement): bool
    {
        return $this->update($user, $announcement);
    }

    /**
     * Determine whether the user can delete several models at once.
     */
    public function deleteAny(User $user): bool
    {
        return false;
    }

    protected function wrote(User $user, Announcement $announcement): bool
    {
        return $user->isTeacher() && $announcement->author_id === $user->getKey();
    }
}
