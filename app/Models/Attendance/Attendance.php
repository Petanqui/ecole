<?php

namespace App\Models\Attendance;

use App\Enums\Attendance\AttendanceStatus;
use App\Models\Core\Enrollment;
use App\Models\User;
use Database\Factories\Attendance\AttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A student absent or late at a roll call, with the justification once the school accepted one.
 */
#[Fillable(['attendance_session_id', 'enrollment_id', 'status'])]
class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
            'justified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AttendanceSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    /**
     * @return BelongsTo<Enrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function justifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'justified_by');
    }

    public function isJustified(): bool
    {
        return $this->justified_at !== null;
    }

    /**
     * Accept the reason given for the absence or the late arrival (medical certificate, family event…).
     */
    public function justify(User $user, string $reason): void
    {
        $this->forceFill([
            'justification' => $reason,
            'justified_at' => now(),
            'justified_by' => $user->getKey(),
        ])->save();
    }

    public function unjustify(): void
    {
        $this->forceFill([
            'justification' => null,
            'justified_at' => null,
            'justified_by' => null,
        ])->save();
    }

    /**
     * Hours missed: the length of the lesson for an absence, none for a late arrival.
     */
    public function missedHours(): int
    {
        return $this->status === AttendanceStatus::Absent ? $this->session->hours : 0;
    }
}
