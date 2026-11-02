<?php

namespace App\Domains\Tenancy\Actions;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\DTOs\RegisterClinicData;
use App\Domains\Tenancy\Models\Plan;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Models\Domain;
use Throwable;

class RegisterClinic
{
    private const TRIAL_DAYS = 14;

    private const DEFAULT_UNIT = 'Unidade principal';

    private const MAX_SLUG_LENGTH = 50;

    private const RESERVED_SLUGS = ['www', 'api', 'admin', 'app', 'mail'];

    /** Creates the clinic, its database, its subdomain and its first admin. */
    public function handle(RegisterClinicData $data): Tenant
    {
        $tenant = new Tenant([
            'name' => $data->clinicName,
            'unit' => $data->unit ?: self::DEFAULT_UNIT,
            'plan_id' => Plan::query()->where('code', Plan::TRIAL_CODE)->valueOrFail('id'),
            'trial_ends_at' => now()->addDays(self::TRIAL_DAYS),
        ]);

        // Saving creates and migrates the clinic database, which a transaction cannot roll back.
        try {
            $tenant->save();
            $tenant->createDomain(['domain' => $this->uniqueSlug($data->clinicName)]);
            $tenant->run(fn () => User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
                'role' => UserRole::Admin,
                'attends_patients' => true,
            ]));
        } catch (Throwable $exception) {
            $this->discard($tenant);

            throw $exception;
        }

        return $tenant;
    }

    /** Turns the clinic name into a free subdomain. */
    private function uniqueSlug(string $clinicName): string
    {
        $base = rtrim(Str::limit(Str::slug($clinicName), self::MAX_SLUG_LENGTH, ''), '-') ?: 'clinica';
        $slug = $base;

        for ($suffix = 2; $this->isTaken($slug); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }

    /** Tells whether the subdomain is reserved or already used. */
    private function isTaken(string $slug): bool
    {
        return in_array($slug, self::RESERVED_SLUGS, true)
            || Domain::query()->where('domain', $slug)->exists();
    }

    /** Removes a half-created clinic and its database. */
    private function discard(Tenant $tenant): void
    {
        if (! $tenant->exists) {
            return;
        }

        try {
            $tenant->delete();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
