<?php

namespace App\Http\Requests;

use App\Domains\Agenda\DTOs\BookingRequestData;
use App\Domains\Agenda\Models\Patient;
use App\Http\Requests\Concerns\HasAppointmentRules;
use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
{
    use HasAppointmentRules;

    /**
     * Validates the public booking form.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $maxDay = today()->addDays(PublicAvailabilityRequest::MAX_DAYS_AHEAD)->toDateString();

        return [
            ...$this->timeRules(),
            'date' => ['required', 'date_format:Y-m-d', 'after:today', "before_or_equal:{$maxDay}"],
            'professional_id' => ['nullable', 'integer', $this->attendingProfessionalRule()],
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'phone' => ['required', 'string', 'max:30', function (string $attribute, mixed $value, \Closure $fail) {
                $length = strlen(Patient::digits((string) $value));

                if ($length < 10 || $length > 13) {
                    $fail('Informe um telefone com DDD.');
                }
            }],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'consent' => ['accepted'],
            'website' => ['nullable', 'string'],
        ];
    }

    /**
     * Messages of the public booking form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->timeMessages(),
            'date.after' => 'Escolha uma data a partir de amanhã.',
            'date.before_or_equal' => 'Escolha uma data nos próximos 60 dias.',
            'name.required' => 'Informe seu nome completo.',
            'name.min' => 'Informe seu nome completo.',
            'phone.required' => 'Informe um telefone com DDD.',
            'email.email' => 'Informe um e-mail válido.',
            'consent.accepted' => 'Autorize o contato da clínica para continuar.',
        ];
    }

    /** Bots fill the hidden field that people never see. */
    public function isBot(): bool
    {
        return $this->filled('website');
    }

    /** Converts the request into the booking DTO. */
    public function toData(): BookingRequestData
    {
        return new BookingRequestData(
            name: trim($this->string('name')),
            phone: trim($this->string('phone')),
            email: $this->input('email'),
            procedure: $this->procedure(),
            professionalId: $this->filled('professional_id') ? $this->integer('professional_id') : null,
            startsAt: $this->startsAt(),
            notes: $this->input('notes'),
        );
    }
}
