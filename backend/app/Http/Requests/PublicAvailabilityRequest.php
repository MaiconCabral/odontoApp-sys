<?php

namespace App\Http\Requests;

use App\Domains\Agenda\Enums\Procedure;
use App\Http\Requests\Concerns\HasAppointmentRules;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicAvailabilityRequest extends FormRequest
{
    use HasAppointmentRules;

    public const MAX_DAYS_AHEAD = 60;

    /**
     * Validates the day searched by a patient: from tomorrow up to 60 days.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'after:today', 'before_or_equal:'.today()->addDays(self::MAX_DAYS_AHEAD)->toDateString()],
            'procedure' => ['required', Rule::enum(Procedure::class)],
            'professional_id' => ['nullable', 'integer', $this->attendingProfessionalRule()],
        ];
    }

    /**
     * Messages of the public search.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->timeMessages(),
            'date.after' => 'Escolha uma data a partir de amanhã.',
            'date.before_or_equal' => 'Escolha uma data nos próximos 60 dias.',
        ];
    }

    /** Day to search. */
    public function day(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $this->input('date'))->startOfDay();
    }
}
