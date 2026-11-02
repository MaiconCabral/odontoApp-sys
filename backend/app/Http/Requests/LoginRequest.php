<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
{
    /**
     * Validates the login form.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Messages of the login form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Informe um e-mail válido.',
            'email.email' => 'Informe um e-mail válido.',
            'password.required' => 'Informe sua senha.',
        ];
    }

    /**
     * Credentials ready for the auth guard.
     *
     * @return array{email: string, password: string}
     */
    public function credentials(): array
    {
        return [
            'email' => Str::lower(trim($this->string('email'))),
            'password' => (string) $this->input('password'),
        ];
    }
}
