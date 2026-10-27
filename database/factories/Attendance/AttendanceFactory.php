<?php

namespace Database\Factories\Attendance;

use App\Enums\Attendance\AttendanceStatus;
use App\Models\Attendance\Attendance;
use App\Models\Attendance\AttendanceSession;
use App\Models\Core\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_session_id' => AttendanceSession::factory(),
            'enrollment_id' => Enrollment::factory(),
            'status' => AttendanceStatus::Absent,
        ];
    }

    public function late(): static
    {
        return $this->state(['status' => AttendanceStatus::Late]);
    }
}
