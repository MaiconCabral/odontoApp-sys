<?php

namespace App\Http\Requests;

use App\Domains\Agenda\DTOs\AppointmentData;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Agenda\Models\Room;
use App\Http\Requests\Concerns\HasAppointmentRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AppointmentRequest extends FormRequest
{
    use HasAppointmentRules;

    /** Only admins and reception book or move appointments. */
    public function authorize(): bool
    {
        return Gate::allows('manage', Appointment::class);
    }

    /**
     * Validates the appointment form.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->timeRules(),
            'patient_id' => ['required', 'integer', Rule::exists(Patient::class, 'id')],
            'professional_id' => ['required', 'integer', $this->attendingProfessionalRule()],
            'room_id' => ['required', 'integer', Rule::exists(Room::class, 'id')],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Messages of the appointment form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->timeMessages();
    }

    /** Converts the request into the appointment DTO. */
    public function toData(): AppointmentData
    {
        return new AppointmentData(
            patientId: $this->integer('patient_id'),
            professionalId: $this->integer('professional_id'),
            roomId: $this->integer('room_id'),
            procedure: $this->procedure(),
            startsAt: $this->startsAt(),
            notes: $this->input('notes'),
        );
    }
}
