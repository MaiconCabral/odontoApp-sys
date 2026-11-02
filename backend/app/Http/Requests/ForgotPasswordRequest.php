<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasPasswordRules;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    use HasPasswordRules;

    /**
     * Validates the forgot password form.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    /**
     * Messages of the forgot password form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Informe um e-mail válido.',
            'email.email' => 'Informe um e-mail válido.',
        ];
    }
}
