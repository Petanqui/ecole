<?php

namespace App\Models\Core;

use App\Enums\Core\Gender;
use Database\Factories\Core\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

#[Fillable(['family_id', 'mena_number', 'last_name', 'first_name', 'gender', 'birth_date', 'birth_place'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    /**
     * Give every new student an internal code (EL-AAAA-NNNN) when it is first saved.
     */
    protected static function booted(): void
    {
        static::creating(function (Student $student): void {
            $student->code ??= static::nextCode((int) now()->year);
        });
    }

    /**
     * Reserve the next internal code for the given year, e.g. "EL-2026-0001".
     *
     * The per-year counter row is locked while it is incremented, so two
     * registrations made at the same time can never receive the same code.
     */
    public static function nextCode(int $year): string
    {
        $number = DB::transaction(function () use ($year): int {
            DB::table('student_code_sequences')->insertOrIgnore(['year' => $year, 'last_number' => 0]);

            $lastNumber = DB::table('student_code_sequences')
                ->where('year', $year)
                ->lockForUpdate()
                ->value('last_number');

            DB::table('student_code_sequences')
                ->where('year', $year)
                ->update(['last_number' => $lastNumber + 1]);

            return $lastNumber + 1;
        });

        return sprintf('EL-%d-%04d', $year, $number);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'birth_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * The enrollment of the student for the current school year, if any.
     *
     * @return HasOne<Enrollment, $this>
     */
    public function currentEnrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class)->whereRelation('schoolYear', 'is_current', true);
    }

    /**
     * Normalize a MENA number: upper case without spaces, or null when left blank.
     */
    public static function normalizeMenaNumber(?string $menaNumber): ?string
    {
        return filled($menaNumber) ? strtoupper(preg_replace('/\s+/', '', $menaNumber)) : null;
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function menaNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => static::normalizeMenaNumber($value),
        );
    }

    /**
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => "{$this->last_name} {$this->first_name}",
        );
    }
}
