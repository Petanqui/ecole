<?php

namespace App\Models\Grades;

use App\Enums\Grades\AdmissionExam;
use App\Models\Core\Enrollment;
use Database\Factories\Grades\AdmissionExamScoreFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['enrollment_id', 'exam', 'score'])]
class AdmissionExamScore extends Model
{
    /** @use HasFactory<AdmissionExamScoreFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exam' => AdmissionExam::class,
            'score' => 'float',
        ];
    }

    /**
     * @return BelongsTo<Enrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
