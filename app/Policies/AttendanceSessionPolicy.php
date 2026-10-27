<?php

namespace App\Policies;

use App\Models\Attendance\AttendanceSession;
use App\Models\User;

/**
 * Staff manage every roll call. A teacher takes the roll calls of the subjects they teach,
 * and can no longer change them once the term is closed.
 */
class AttendanceSessionPolicy
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
    public function view(User $user, AttendanceSession $attendanceSession): bool
    {
        return $user->isStaffMember() || $this->teaches($user, $attendanceSession);
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
    public function update(User $user, AttendanceSession $attendanceSession): bool
    {
        if ($user->isStaffMember()) {
            return true;
        }

        return $this->teaches($user, $attendanceSession) && ! $attendanceSession->term->is_closed;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AttendanceSession $attendanceSession): bool
    {
        return $this->update($user, $attendanceSession);
    }

    /**
     * Determine whether the user can delete several models at once.
     */
    public function deleteAny(User $user): bool
    {
        return false;
    }

    protected function teaches(User $user, AttendanceSession $attendanceSession): bool
    {
        return $user->isTeacher() && $attendanceSession->classSubject->teacher_id === $user->getKey();
    }
}
