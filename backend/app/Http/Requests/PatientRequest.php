<?php

namespace App\Http\Requests;

use App\Domains\Agenda\DTOs\PatientData;
use App\Domains\Agenda\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PatientRequest extends FormRequest
{
    /** Only admins and reception keep patient records. */
    public function authorize(): bool
    {
        $patient = $this->route('patient');

        return $patient ? Gate::allows('update', $patient) : Gate::allows('create', Patient::class);
    }

    /** Keeps only the CPF digits, so the unique rule compares the same format as the database. */
    protected function prepareForValidation(): void
    {
        $digits = Patient::digits($this->input('cpf'));

        $this->merge([
            'cpf' => strlen($digits) === 11 ? preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $digits) : ($digits ?: null),
        ]);
    }

    /**
     * Validates the patient form.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'phone' => ['required', 'string', 'max:30', function (string $attribute, mixed $value, \Closure $fail) {
                $length = strlen(Patient::digits((string) $value));

                if ($length < 10 || $length > 13) {
                    $fail('Informe um telefone com DDD.');
                }
            }],
            'email' => ['nullable', 'email', 'max:255'],
            'cpf' => ['nullable', 'regex:/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', Rule::unique(Patient::class, 'cpf')->ignore($this->route('patient'))],
            'insurance' => ['nullable', 'string', 'max:60'],
            'birth_date' => ['nullable', 'date_format:Y-m-d', 'before:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Messages of the patient form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome completo.',
            'name.min' => 'Informe o nome completo.',
            'phone.required' => 'Informe um telefone com DDD.',
            'email.email' => 'Informe um e-mail válido.',
            'cpf.regex' => 'Informe um CPF com 11 dígitos.',
            'cpf.unique' => 'Já existe um paciente com este CPF.',
            'birth_date.before' => 'Informe uma data de nascimento válida.',
            'birth_date.date_format' => 'Informe uma data de nascimento válida.',
        ];
    }

    /** Converts the request into the patient DTO. */
    public function toData(): PatientData
    {
        return new PatientData(
            name: trim($this->string('name')),
            phone: trim($this->string('phone')),
            email: $this->input('email'),
            cpf: $this->input('cpf'),
            insurance: $this->input('insurance'),
            birthDate: $this->input('birth_date'),
            notes: $this->input('notes'),
        );
    }
}
