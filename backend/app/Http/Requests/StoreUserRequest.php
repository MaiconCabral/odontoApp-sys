<?php

namespace App\Http\Requests;

use App\Domains\Identity\DTOs\UserData;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /** Only admins may add team members. */
    public function authorize(): bool
    {
        return Gate::allows('create', User::class);
    }

    /**
     * Validates the team member form.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', $this->uniqueEmail()],
            'role' => ['required', Rule::enum(UserRole::class)],
            'specialty' => ['nullable', 'string', 'max:255'],
            'attends_patients' => ['sometimes', 'boolean'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Messages of the team member form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome completo.',
            'name.min' => 'Informe o nome completo.',
            'email.required' => 'Informe um e-mail válido.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está em uso.',
            'role.required' => 'Selecione um perfil de acesso.',
            'role.enum' => 'Selecione um perfil de acesso.',
        ];
    }

    /** Converts the request into the user DTO. */
    public function toData(): UserData
    {
        return new UserData(
            name: trim($this->string('name')),
            email: Str::lower(trim($this->string('email'))),
            role: $this->enum('role', UserRole::class),
            specialty: $this->input('specialty'),
            attendsPatients: $this->boolean('attends_patients'),
            active: $this->boolean('active', true),
        );
    }

    /** Normalizes the e-mail before validating. */
    protected function prepareForValidation(): void
    {
        $this->merge(['email' => Str::lower(trim((string) $this->input('email')))]);
    }

    /** E-mail must not exist in this clinic. */
    protected function uniqueEmail(): mixed
    {
        return Rule::unique(User::class, 'email');
    }
}
