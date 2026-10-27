<?php

namespace App\Models\Grades;

use App\Models\Core\Student;
use Database\Factories\Grades\MarkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['assessment_id', 'student_id', 'score', 'is_absent'])]
class Mark extends Model
{
    /** @use HasFactory<MarkFactory> */
    use HasFactory;

    /**
     * An absent student has no score.
     */
    protected static function booted(): void
    {
        static::saving(function (Mark $mark): void {
            if ($mark->is_absent) {
                $mark->score = null;
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'float',
            'is_absent' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Assessment, $this>
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
