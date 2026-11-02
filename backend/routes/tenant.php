<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AgendaController;
use App\Http\Controllers\Api\ClinicController;
use App\Http\Controllers\Api\PasswordController;
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

        Route::middleware(['auth:sanctum', EnsureUserIsActive::class])->group(function () {
            Route::get('session', [SessionController::class, 'show']);
            Route::post('logout', [SessionController::class, 'destroy']);

            Route::get('clinic', [ClinicController::class, 'show']);
            Route::put('clinic', [ClinicController::class, 'update']);

            Route::apiResource('users', UserController::class);

            Route::get('agenda', AgendaController::class);
        });
    });
