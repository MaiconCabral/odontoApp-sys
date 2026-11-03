<?php

namespace App\Domains\Tenancy\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /** Service preferences kept in the data column, with their defaults. */
    public const PREFERENCES = [
        'auto_confirmation' => true,
        'waiting_list' => true,
        'reminder_24h' => true,
    ];

    /** Casts the trial end to a date. */
    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
        ];
    }

    /**
     * Lists the columns stored outside the JSON data column.
     *
     * @return list<string>
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'unit',
            'cnpj',
            'phone',
            'email',
            'address',
            'plan_id',
            'trial_ends_at',
            'created_at',
            'updated_at',
        ];
    }

    /** Plan the clinic subscribes to. */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** Subdomain that identifies the clinic. */
    public function slug(): string
    {
        return (string) $this->domains->first()?->domain;
    }

    /** Days left in the trial, never below zero. */
    public function trialDaysLeft(): int
    {
        if (! $this->trial_ends_at) {
            return 0;
        }

        return max(0, (int) ceil(now()->diffInDays($this->trial_ends_at)));
    }

    /** Trial while it runs, expired after it, active once there is no trial. */
    public function subscriptionStatus(): string
    {
        if (! $this->trial_ends_at) {
            return 'active';
        }

        return $this->trial_ends_at->isFuture() ? 'trial' : 'expired';
    }

    /**
     * Service preferences, falling back to the defaults.
     *
     * @return array<string, bool>
     */
    public function preferences(): array
    {
        $preferences = [];

        foreach (self::PREFERENCES as $key => $default) {
            $preferences[$key] = (bool) ($this->getAttribute($key) ?? $default);
        }

        return $preferences;
    }

    /** Whether the clinic sends the 24h reminder. */
    public function sendsReminders(): bool
    {
        return $this->preferences()['reminder_24h'];
    }
}
