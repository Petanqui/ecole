<?php

namespace App\Models\Core;

use App\Enums\Grades\AdmissionExam;
use App\Models\Billing\Fee;
use App\Models\Billing\Payment;
use App\Models\Grades\AdmissionExamScore;
use Database\Factories\Core\EnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['student_id', 'school_class_id', 'enrolled_on'])]
class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    /**
     * The school year always follows the class, so the two can never disagree.
     */
    protected static function booted(): void
    {
        static::saving(function (Enrollment $enrollment): void {
            if ($enrollment->isDirty('school_class_id')) {
                $enrollment->school_year_id = SchoolClass::whereKey($enrollment->school_class_id)->value('school_year_id');
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
            'enrolled_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<SchoolYear, $this>
     */
    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * @return BelongsTo<SchoolClass, $this>
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * The optional fees (canteen, transport…) the student subscribed to.
     *
     * @return BelongsToMany<Fee, $this>
     */
    public function optionalFees(): BelongsToMany
    {
        return $this->belongsToMany(Fee::class)->where('is_optional', true);
    }

    /**
     * The fees the student owes this year: the compulsory fees of their level and the optional
     * fees they subscribed to, with their installments.
     *
     * @return Collection<int, Fee>
     */
    public function applicableFees(): Collection
    {
        return Fee::query()
            ->forLevel($this->school_year_id, $this->schoolClass->level)
            ->where(fn (Builder $query) => $query
                ->where('is_optional', false)
                ->orWhereHas('enrollments', fn (Builder $query) => $query->whereKey($this->getKey())))
            ->with('installments')
            ->orderBy('is_optional')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return HasMany<AdmissionExamScore, $this>
     */
    public function admissionExamScores(): HasMany
    {
        return $this->hasMany(AdmissionExamScore::class);
    }

    public function admissionExamScore(AdmissionExam $exam): ?float
    {
        return $this->admissionExamScores->firstWhere('exam', $exam)?->score;
    }

    /**
     * Record (or, with null, remove) the score of a CM2 admission exam.
     */
    public function setAdmissionExamScore(AdmissionExam $exam, ?float $score): void
    {
        if ($score === null) {
            $this->admissionExamScores()->where('exam', $exam)->delete();
        } else {
            $this->admissionExamScores()->updateOrCreate(['exam' => $exam], ['score' => $score]);
        }

        $this->unsetRelation('admissionExamScores');
    }

    /**
     * MAP (moyenne d'admission en 6e) = mean of the four exams, out of 20; null until all four are entered.
     */
    public function admissionAverage(): ?float
    {
        if ($this->admissionExamScores->count() < count(AdmissionExam::cases())) {
            return null;
        }

        return round($this->admissionExamScores->avg('score'), 2);
    }
}
