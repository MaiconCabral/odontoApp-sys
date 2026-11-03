<?php

use App\Http\Controllers\Api\ClinicRegistrationController;
use App\Http\Controllers\Api\Platform\PlatformClinicController;
use App\Http\Controllers\Api\Platform\PlatformPlanController;
use App\Http\Controllers\Api\Platform\PlatformSessionController;
use Illuminate\Support\Facades\Route;

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        Route::get('/health', fn () => response()->json(['status' => 'ok']));

        Route::post('/clinics', [ClinicRegistrationController::class, 'store'])
            ->middleware('throttle:clinic-registration');

        // Global panel. Platform admins have their own guard and table; clinic users never get in.
        Route::prefix('platform')->group(function () {
            Route::post('login', [PlatformSessionController::class, 'store'])->middleware('throttle:login');

            Route::middleware('auth:platform')->group(function () {
                Route::get('session', [PlatformSessionController::class, 'show']);
                Route::post('logout', [PlatformSessionController::class, 'destroy']);

                Route::get('clinics', [PlatformClinicController::class, 'index']);
                Route::get('clinics/{clinic}', [PlatformClinicController::class, 'show']);
                Route::put('clinics/{clinic}', [PlatformClinicController::class, 'update']);

                Route::apiResource('plans', PlatformPlanController::class)->except('show');
            });
        });
    });
}
