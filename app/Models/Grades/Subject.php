<?php

namespace App\Models\Grades;

use Database\Factories\Grades\SubjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'sort_order'])]
class Subject extends Model
{
    /** @use HasFactory<SubjectFactory> */
    use HasFactory;

    /**
     * Store the code in upper case without spaces.
     */
    protected static function booted(): void
    {
        static::saving(function (Subject $subject): void {
            $subject->code = strtoupper(preg_replace('/\s+/', '', $subject->code));
        });
    }

    /**
     * @return HasMany<ClassSubject, $this>
     */
    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class);
    }
}
