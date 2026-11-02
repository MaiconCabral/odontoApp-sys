<?php

namespace App\Http\Requests;

use App\Domains\Tenancy\DTOs\ClinicData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateClinicRequest extends FormRequest
{
    /** Only admins may change the clinic. */
    public function authorize(): bool
    {
        return Gate::allows('update', tenant());
    }

    /**
     * Validates the clinic data.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'unit' => ['required', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'regex:/^\d{2}\.?\d{3}\.?\d{3}\/?\d{4}-?\d{2}$/'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Messages of the clinic form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome da clínica.',
            'name.min' => 'Informe o nome da clínica.',
            'unit.required' => 'Informe a unidade.',
            'cnpj.regex' => 'Informe um CNPJ válido.',
            'email.email' => 'Informe um e-mail válido.',
        ];
    }

    /** Converts the request into the clinic DTO. */
    public function toData(): ClinicData
    {
        return new ClinicData(
            name: trim($this->string('name')),
            unit: trim($this->string('unit')),
            cnpj: $this->input('cnpj'),
            phone: $this->input('phone'),
            email: $this->input('email'),
            address: $this->input('address'),
        );
    }
}
