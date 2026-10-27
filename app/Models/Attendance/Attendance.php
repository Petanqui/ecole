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
}
