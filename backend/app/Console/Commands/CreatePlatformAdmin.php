<?php

namespace App\Console\Commands;

use App\Domains\Platform\Models\PlatformAdmin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CreatePlatformAdmin extends Command
{
    protected $signature = 'platform:create-admin {email : E-mail de acesso} {--name= : Nome}';

    protected $description = 'Cria ou troca a senha de um administrador da plataforma';

    /** Asks the password without showing it; there is no sign-up page for platform admins. */
    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        $name = $this->option('name') ?: $this->ask('Nome');
        $password = (string) $this->secret('Senha (mínimo de 12 caracteres)');

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $password],
            ['email' => ['required', 'email'], 'name' => ['required', 'string', 'max:255'], 'password' => ['required', Password::min(12)->letters()->numbers()]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $admin = PlatformAdmin::query()->updateOrCreate(['email' => $email], ['name' => $name, 'password' => $password]);

        $this->info($admin->wasRecentlyCreated ? "Administrador {$email} criado." : "Senha de {$email} atualizada.");

        return self::SUCCESS;
    }
}
