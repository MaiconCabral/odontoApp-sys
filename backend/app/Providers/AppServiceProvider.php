<?php

namespace App\Providers;

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Policies\AppointmentPolicy;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Policies\UserPolicy;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Policies\ClinicPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Tenant::class, ClinicPolicy::class);
        Gate::policy(Appointment::class, AppointmentPolicy::class);

        $this->configureRateLimiting();
    }

    /** Limits registration, login and password attempts. */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('clinic-registration', fn (Request $request) => $this->limit(5, $request->ip()));
        RateLimiter::for('login', fn (Request $request) => $this->limit(5, $this->accountKey($request)));
        RateLimiter::for('password', fn (Request $request) => $this->limit(5, $this->accountKey($request)));
    }

    /** Per-minute limit with a Portuguese message. */
    private function limit(int $perMinute, ?string $key): Limit
    {
        return Limit::perMinute($perMinute)
            ->by($key)
            ->response(fn (Request $request, array $headers) => new JsonResponse(
                ['message' => 'Muitas tentativas. Aguarde um minuto e tente novamente.'],
                429,
                $headers,
            ));
    }

    /**
     * Rate limit key per clinic, e-mail and IP.
     * The cache is shared between clinics, so the key carries the tenant.
     */
    private function accountKey(Request $request): string
    {
        return implode('|', [tenant()?->getTenantKey(), Str::lower((string) $request->input('email')), $request->ip()]);
    }
}
