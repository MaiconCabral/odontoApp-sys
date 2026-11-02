<?php

namespace App\Http\Requests;

use App\Domains\Tenancy\DTOs\RegisterClinicData;
use App\Http\Requests\Concerns\HasPasswordRules;
use Illuminate\Foundation\Http\FormRequest;

class RegisterClinicRequest extends FormRequest
{
    use HasPasswordRules;

    /**
     * Validates the clinic registration.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => $this->newPasswordRules(),
            'clinic_name' => ['required', 'string', 'min:2', 'max:255'],
            'unit' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Messages of the clinic registration.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->passwordMessages(),
            'name.required' => 'Informe seu nome completo.',
            'name.min' => 'Informe seu nome completo.',
            'email.required' => 'Informe um e-mail válido.',
            'email.email' => 'Informe um e-mail válido.',
            'clinic_name.required' => 'Informe o nome da clínica.',
            'clinic_name.min' => 'Informe o nome da clínica.',
        ];
    }

    /** Converts the request into the registration DTO. */
    public function toData(): RegisterClinicData
    {
        return new RegisterClinicData(
            name: trim($this->string('name')),
            email: $this->email(),
            password: (string) $this->input('password'),
            clinicName: trim($this->string('clinic_name')),
            unit: trim($this->string('unit')) ?: null,
        );
    }
}
