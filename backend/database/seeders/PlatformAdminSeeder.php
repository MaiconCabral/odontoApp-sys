<?php

namespace Database\Seeders;

use App\Domains\Platform\Models\PlatformAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

// Admin of the global panel, from PLATFORM_ADMIN_EMAIL and PLATFORM_ADMIN_PASSWORD.
// Without both, nothing happens: locally the admin is still made with platform:create-admin.
class PlatformAdminSeeder extends Seeder
{
    /** Creates the admin, or updates its password, when the server sets both values. */
    public function run(): void
    {
        $email = Str::lower(trim((string) env('PLATFORM_ADMIN_EMAIL')));
        $password = (string) env('PLATFORM_ADMIN_PASSWORD');

        if ($email === '' || $password === '') {
            return;
        }

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['required', 'email'], 'password' => ['required', Password::min(12)->letters()->numbers()]],
        );

        if ($validator->fails()) {
            throw new RuntimeException('PLATFORM_ADMIN_*: '.implode(' ', $validator->errors()->all()));
        }

        PlatformAdmin::query()->updateOrCreate(
            ['email' => $email],
            ['name' => env('PLATFORM_ADMIN_NAME', 'Equipe odontoApp'), 'password' => $password],
        );
    }
}
