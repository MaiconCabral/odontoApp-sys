<?php

namespace App\Domains\Identity\Models;

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Notifications\AccountPasswordLink;
use App\Support\Initials;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'specialty',
        'attends_patients',
        'tone',
        'active',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'active' => true,
    ];

    /**
     * Casts password, role and flags.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'attends_patients' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /** Factory used by tests and seeds. */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /** Stores the e-mail trimmed and in lowercase. */
    protected function email(): Attribute
    {
        return Attribute::set(fn (string $value) => Str::lower(trim($value)));
    }

    /** Appointments this user attends. */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'professional_id');
    }

    /** Tells whether the user is an administrator. */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /** Initials shown in the avatar. */
    public function initials(): string
    {
        return Initials::of($this->name);
    }

    /** Sends the password reset link to the clinic frontend. */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new AccountPasswordLink(AccountPasswordLink::urlFor($this, $token)));
    }
}
