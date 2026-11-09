<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reminders of appointments in the next 24 hours, for every clinic.
Schedule::command('appointments:send-reminders')->everyFifteenMinutes()->withoutOverlapping();

// Demo server only (DEMO_MODE=true): wipes every clinic and seeds the demo data again each night.
Schedule::command('demo:reset')
    ->dailyAt('04:00')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping()
    ->when(fn () => config('app.demo'));
