<?php

namespace App\Providers;

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Agenda\Models\Procedure;
use App\Domains\Agenda\Models\Room;
use App\Domains\Agenda\Policies\AppointmentPolicy;
use App\Domains\Agenda\Policies\CatalogPolicy;
use App\Domains\Agenda\Policies\PatientPolicy;
use App\Domains\Agenda\Policies\SchedulePolicy;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Policies\UserPolicy;
use App\Domains\Notifications\Models\NotificationLog;
use App\Domains\Notifications\Notifications\AppointmentMessage;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Policies\ClinicPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
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
        Gate::policy(Patient::class, PatientPolicy::class);
        Gate::policy(Procedure::class, CatalogPolicy::class);
        Gate::policy(Room::class, CatalogPolicy::class);
        Gate::define('updateBusinessHours', [SchedulePolicy::class, 'updateBusinessHours']);
        Gate::define('manageBlocks', [SchedulePolicy::class, 'manageBlocks']);

        $this->configureRateLimiting();
        $this->trackPatientMessages();
    }

    /** Marks the log row of a patient message as sent when the channel delivers it. */
    private function trackPatientMessages(): void
    {
        Event::listen(NotificationSent::class, function (NotificationSent $event) {
            if ($event->notification instanceof AppointmentMessage) {
                NotificationLog::query()->whereKey($event->notification->logId)->update(['status' => NotificationLog::STATUS_SENT]);
            }
        });
    }

    /** Limits registration, login and password attempts. */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('clinic-registration', fn (Request $request) => $this->limit(5, $request->ip()));
        RateLimiter::for('login', fn (Request $request) => $this->limit(5, $this->accountKey($request)));
        RateLimiter::for('password', fn (Request $request) => $this->limit(5, $this->accountKey($request)));
        RateLimiter::for('public-read', fn (Request $request) => $this->limit(60, $this->visitorKey($request)));
        RateLimiter::for('patient-link', fn (Request $request) => $this->limit(10, $this->visitorKey($request)));
        RateLimiter::for('booking', fn (Request $request) => Limit::perHour(5)->by($this->visitorKey($request))->response(
            fn (Request $request, array $headers) => new JsonResponse(['message' => 'Você já enviou vários pedidos. Tente de novo mais tarde ou ligue para a clínica.'], 429, $headers),
        ));
    }

    /** Rate limit key per clinic and IP, for visitors without an account. */
    private function visitorKey(Request $request): string
    {
        return implode('|', [tenant()?->getTenantKey(), $request->ip()]);
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
