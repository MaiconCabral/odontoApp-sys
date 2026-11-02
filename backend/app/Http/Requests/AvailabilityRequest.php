<?php

namespace App\Http\Requests;

use App\Domains\Agenda\Enums\Procedure;
use App\Domains\Agenda\Models\Room;
use App\Http\Requests\Concerns\HasAppointmentRules;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AvailabilityRequest extends FormRequest
{
    use HasAppointmentRules;

    /**
     * Validates the day, procedure, professional and room to search.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'procedure' => ['required', Rule::enum(Procedure::class)],
            'professional_id' => ['required', 'integer', $this->attendingProfessionalRule()],
            'room_id' => ['nullable', 'integer', Rule::exists(Room::class, 'id')],
            'ignore' => ['nullable', 'integer'],
        ];
    }

    /**
     * Messages of the search.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [...$this->timeMessages(), 'date.after_or_equal' => 'Escolha hoje ou uma data futura.'];
    }

    /** Day to search. */
    public function day(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $this->input('date'))->startOfDay();
    }
}
