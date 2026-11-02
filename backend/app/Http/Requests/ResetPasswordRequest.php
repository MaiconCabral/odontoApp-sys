<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasPasswordRules;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    use HasPasswordRules;

    /**
     * Validates the reset password form.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => $this->newPasswordRules(),
        ];
    }

    /**
     * Messages of the reset password form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->passwordMessages(),
            'token.required' => 'Este link é inválido ou expirou.',
            'email.required' => 'Este link é inválido ou expirou.',
            'email.email' => 'Este link é inválido ou expirou.',
        ];
    }
}
