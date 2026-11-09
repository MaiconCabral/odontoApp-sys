<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Plans always; demo clinics in local development and on the demo server; the platform admin when configured. */
    public function run(): void
    {
        $this->call(PlanSeeder::class);

        if (app()->environment('local') || config('app.demo')) {
            $this->call(DemoTenantSeeder::class);
        }

        $this->call(PlatformAdminSeeder::class);
    }
}
