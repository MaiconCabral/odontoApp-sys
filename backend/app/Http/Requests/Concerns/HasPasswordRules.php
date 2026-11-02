<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

trait HasPasswordRules
{
    /**
     * Rules for a new password.
     *
     * @return list<mixed>
     */
    protected function newPasswordRules(): array
    {
        return ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()];
    }

    /**
     * Messages for the new password rules.
     *
     * @return array<string, string>
     */
    protected function passwordMessages(): array
    {
        $weak = 'Use ao menos 8 caracteres, com letras e números.';

        return [
            'password.required' => $weak,
            'password.min' => $weak,
            'password.letters' => $weak,
            'password.numbers' => $weak,
            'password.confirmed' => 'As senhas não coincidem.',
        ];
    }

    /** E-mail trimmed and in lowercase. */
    public function email(): string
    {
        return Str::lower(trim((string) $this->input('email')));
    }
}
