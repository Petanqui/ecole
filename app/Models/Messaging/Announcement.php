<?php

namespace App\Models\Messaging;

use App\Enums\Core\GradeLevel;
use App\Enums\Messaging\AnnouncementAudience;
use App\Models\Core\Enrollment;
use App\Models\Core\SchoolClass;
use App\Models\Core\SchoolYear;
use App\Models\Core\Student;
use App\Models\User;
use App\Policies\AnnouncementPolicy;
use Database\Factories\Messaging\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A message from the school to the parents of the whole school, a level or a class, for a school year.
 * Parents cannot reply to it. Its recipients follow the enrollments: a parent whose portal account
 * is created later still sees it.
 */
#[Fillable(['school_year_id', 'audience', 'level', 'school_class_id', 'title', 'body', 'author_id'])]
#[UsePolicy(AnnouncementPolicy::class)]
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    /**
     * Keep only the target matching the audience; a class announcement belongs to the year of its class.
     */
    protected static function booted(): void
    {
        static::saving(function (Announcement $announcement): void {
            if ($announcement->audience !== AnnouncementAudience::Level) {
                $announcement->level = null;
            }

            if ($announcement->audience !== AnnouncementAudience::SchoolClass) {
                $announcement->school_class_id = null;
            } else {
                $announcement->school_year_id = SchoolClass::whereKey($announcement->school_class_id)->value('school_year_id');
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
            'audience' => AnnouncementAudience::class,
            'level' => GradeLevel::class,
        ];
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
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * The parents who opened the announcement.
     *
     * @return BelongsToMany<User, $this>
     */
    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_reads')->withPivot('read_at');
    }

    /**
     * "Toute l'école", "Niveau CM2", "Classe 6e1".
     *
     * @return Attribute<string, never>
     */
    protected function audienceLabel(): Attribute
    {
        return Attribute::make(get: fn (): string => match ($this->audience) {
            AnnouncementAudience::School => 'Toute l\'école',
            AnnouncementAudience::Level => "Niveau {$this->level?->getLabel()}",
            AnnouncementAudience::SchoolClass => "Classe {$this->schoolClass?->name}",
        });
    }

    /**
     * The enrollments the announcement is addressed to.
     *
     * @return Builder<Enrollment>
     */
    public function addressedEnrollments(): Builder
    {
        return Enrollment::query()
            ->where('school_year_id', $this->school_year_id)
            ->when($this->audience === AnnouncementAudience::Level, fn (Builder $query) => $query->whereRelation('schoolClass', 'level', $this->level))
            ->when($this->audience === AnnouncementAudience::SchoolClass, fn (Builder $query) => $query->where('school_class_id', $this->school_class_id));
    }

    /**
     * The portal accounts of the parents of the addressed students.
     *
     * @return Builder<User>
     */
    public function recipients(): Builder
    {
        return User::query()->whereHas('guardian', fn (Builder $query) => $query->whereIn(
            'family_id',
            Student::query()->whereIn('id', $this->addressedEnrollments()->select('student_id'))->select('family_id'),
        ));
    }

    public function markAsReadBy(User $user): void
    {
        $this->readers()->syncWithoutDetaching([$user->getKey() => ['read_at' => now()]]);
    }

    /**
     * Announcements addressed to one of the children of the parent, whatever the school year.
     *
     * @param  Builder<Announcement>  $query
     */
    #[Scope]
    protected function addressedTo(Builder $query, User $parent): void
    {
        $enrollments = Enrollment::query()
            ->whereIn('student_id', Student::query()->where('family_id', $parent->guardian?->family_id)->select('id'))
            ->with('schoolClass')
            ->get();

        $query->where(function (Builder $query) use ($enrollments): void {
            $query->whereRaw('1 = 0');

            foreach ($enrollments as $enrollment) {
                $query->orWhere(fn (Builder $query) => $query
                    ->where('school_year_id', $enrollment->school_year_id)
                    ->where(fn (Builder $query) => $query
                        ->where('audience', AnnouncementAudience::School)
                        ->orWhere(fn (Builder $query) => $query->where('audience', AnnouncementAudience::Level)->where('level', $enrollment->schoolClass->level))
                        ->orWhere(fn (Builder $query) => $query->where('audience', AnnouncementAudience::SchoolClass)->where('school_class_id', $enrollment->school_class_id))));
            }
        });
    }

    /**
     * Announcements the parent has not opened yet.
     *
     * @param  Builder<Announcement>  $query
     */
    #[Scope]
    protected function unreadBy(Builder $query, User $parent): void
    {
        $query->whereDoesntHave('readers', fn (Builder $query) => $query->whereKey($parent->getKey()));
    }

    /**
     * Staff see every announcement; a teacher only their own.
     *
     * @param  Builder<Announcement>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if (! $user->isStaffMember()) {
            $query->where('author_id', $user->getKey());
        }
    }
}
