<?php

namespace App\Policies;

use App\Models\Grades\Assessment;
use App\Models\User;

/**
 * Staff manage every assessment. A teacher manages the assessments of the subjects they
 * teach, and can no longer change them once the term is closed.
 */
class AssessmentPolicy
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
    public function view(User $user, Assessment $assessment): bool
    {
        return $user->isStaffMember() || $this->teaches($user, $assessment);
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
    public function update(User $user, Assessment $assessment): bool
    {
        if ($user->isStaffMember()) {
            return true;
        }

        return $this->teaches($user, $assessment) && ! $assessment->term->is_closed;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Assessment $assessment): bool
    {
        return $this->update($user, $assessment);
    }

    /**
     * Determine whether the user can delete several models at once.
     */
    public function deleteAny(User $user): bool
    {
        return false;
    }

    protected function teaches(User $user, Assessment $assessment): bool
    {
        return $user->isTeacher() && $assessment->classSubject->teacher_id === $user->getKey();
    }
}
