<?php

use App\Http\Controllers\Api\ClinicRegistrationController;
use Illuminate\Support\Facades\Route;

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        Route::get('/health', fn () => response()->json(['status' => 'ok']));

        Route::post('/clinics', [ClinicRegistrationController::class, 'store'])
            ->middleware('throttle:clinic-registration');
    });
}
