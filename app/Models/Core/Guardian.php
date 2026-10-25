<?php

namespace App\Models\Core;

use App\Enums\Core\GuardianRelationship;
use App\Enums\Core\UserRole;
use App\Exceptions\Core\PhoneNumberAlreadyUsedException;
use App\Models\User;
use App\Support\PhoneNumber;
use Database\Factories\Core\GuardianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable(['family_id', 'relationship', 'last_name', 'first_name', 'phone', 'email', 'profession'])]
class Guardian extends Model
{
    /** @use HasFactory<GuardianFactory> */
    use HasFactory;

    /**
     * A parent's portal account goes away with the parent, and follows changes to their name and phone.
     */
    protected static function booted(): void
    {
        static::deleted(function (Guardian $guardian): void {
            $guardian->user?->delete();
        });

        static::updated(function (Guardian $guardian): void {
            if ($guardian->user && $guardian->wasChanged(['last_name', 'first_name', 'phone'])) {
                $guardian->user->update(['name' => $guardian->full_name, 'phone' => $guardian->phone]);
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
            'relationship' => GuardianRelationship::class,
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
     * The portal account of this parent, if one has been created.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create the portal account of this parent, signing in with their phone number.
     *
     * Returns the generated password so staff can hand it over to the parent.
     *
     * @throws PhoneNumberAlreadyUsedException when another account already uses this phone number
     */
    public function createPortalAccount(): string
    {
        if (User::where('phone', $this->phone)->exists()) {
            throw new PhoneNumberAlreadyUsedException($this->phone);
        }

        $password = static::generatePassword();

        DB::transaction(function () use ($password): void {
            $user = User::create([
                'name' => $this->full_name,
                'phone' => $this->phone,
                'email' => $this->email,
                'role' => UserRole::Guardian,
                'password' => $password,
            ]);

            $this->user()->associate($user)->save();
        });

        return $password;
    }

    /**
     * Give the parent a new password, e.g. when they forgot theirs. Returns the new password.
     */
    public function resetPortalPassword(): string
    {
        $password = static::generatePassword();

        $this->user->update(['password' => $password]);

        return $password;
    }

    /**
     * A password that is easy to read out or send by SMS: no symbols and no look-alike characters.
     */
    protected static function generatePassword(): string
    {
        return Str::of(Str::password(32, symbols: false))
            ->replaceMatches('/[0OoIl1]/', '')
            ->substr(0, 8)
            ->value();
    }

    /**
     * Store the phone number in E.164 format.
     *
     * @return Attribute<string, string>
     */
    protected function phone(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => PhoneNumber::normalize($value) ?? $value,
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
