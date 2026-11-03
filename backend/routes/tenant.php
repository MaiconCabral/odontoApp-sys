<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AgendaController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\ClinicController;
use App\Http\Controllers\Api\OverviewController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PatientLinkController;
use App\Http\Controllers\Api\ProcedureController;
use App\Http\Controllers\Api\PublicBookingController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Tenancy starts before the "api" group on purpose: Sanctum's stateful
| middleware loads the session user, and that user lives in the clinic database.
| AuthenticateTenantSession refuses sessions that belong to another clinic.
|
*/

Route::prefix('api')
    ->middleware([
        PreventAccessFromCentralDomains::class,
        InitializeTenancyBySubdomain::class,
        'api',
    ])
    ->group(function () {
        Route::post('login', [SessionController::class, 'store'])->middleware('throttle:login');
        Route::post('forgot-password', [PasswordController::class, 'forgot'])->middleware('throttle:password');
        Route::post('reset-password', [PasswordController::class, 'reset'])->middleware('throttle:password');

        Route::prefix('public')->group(function () {
            Route::get('booking', [PublicBookingController::class, 'show'])->middleware('throttle:public-read');
            Route::get('availability', [PublicBookingController::class, 'availability'])->middleware('throttle:public-read');
            Route::post('booking-requests', [PublicBookingController::class, 'store'])->middleware('throttle:booking');
            Route::get('appointments/{token}', [PatientLinkController::class, 'show'])->middleware('throttle:public-read');
            Route::post('appointments/{token}/confirm', [PatientLinkController::class, 'confirm'])->middleware('throttle:patient-link');
            Route::post('appointments/{token}/cancel', [PatientLinkController::class, 'cancel'])->middleware('throttle:patient-link');
        });

        Route::middleware(['auth:sanctum', EnsureUserIsActive::class])->group(function () {
            Route::get('session', [SessionController::class, 'show']);
            Route::post('logout', [SessionController::class, 'destroy']);

            Route::get('clinic', [ClinicController::class, 'show']);
            Route::put('clinic', [ClinicController::class, 'update']);

            Route::apiResource('users', UserController::class);
            Route::apiResource('patients', PatientController::class);
            Route::apiResource('procedures', ProcedureController::class);
            Route::apiResource('rooms', RoomController::class);

            Route::get('agenda', AgendaController::class);
            Route::get('overview', OverviewController::class);
            Route::get('availability', AvailabilityController::class);

            Route::get('appointment-requests', [AppointmentController::class, 'requests']);
            Route::post('appointments', [AppointmentController::class, 'store']);
            Route::get('appointments/{appointment}', [AppointmentController::class, 'show']);
            Route::put('appointments/{appointment}', [AppointmentController::class, 'update']);
            Route::post('appointments/{appointment}/status', [AppointmentController::class, 'status']);
            Route::post('appointments/{appointment}/accept', [AppointmentController::class, 'accept']);

            Route::get('business-hours', [ScheduleController::class, 'hours']);
            Route::put('business-hours', [ScheduleController::class, 'updateHours']);
            Route::get('schedule-blocks', [ScheduleController::class, 'blocks']);
            Route::post('schedule-blocks', [ScheduleController::class, 'storeBlock']);
            Route::put('schedule-blocks/{block}', [ScheduleController::class, 'updateBlock']);
            Route::delete('schedule-blocks/{block}', [ScheduleController::class, 'destroyBlock']);
        });
    });
