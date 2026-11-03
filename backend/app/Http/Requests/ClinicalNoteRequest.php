<?php

namespace App\Http\Requests;

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Clinical\DTOs\ClinicalNoteData;
use App\Domains\Clinical\Models\ClinicalNote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ClinicalNoteRequest extends FormRequest
{
    /** Who sees the medical record of the patient may write in it. */
    public function authorize(): bool
    {
        return Gate::allows('writeRecord', $this->patient());
    }

    /**
     * Validates the note; the appointment and the corrected note must be of the same patient.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:2', 'max:5000'],
            'appointment_id' => ['nullable', 'integer', Rule::exists(Appointment::class, 'id')->where('patient_id', $this->patient()->id)],
            'amends_id' => ['nullable', 'integer', Rule::exists(ClinicalNote::class, 'id')->where('patient_id', $this->patient()->id)],
        ];
    }

    /**
     * Messages of the note form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'Escreva a anotação.',
            'body.min' => 'Escreva a anotação.',
            'body.max' => 'A anotação pode ter até 5.000 caracteres.',
            'appointment_id.exists' => 'Escolha uma consulta deste paciente.',
            'amends_id.exists' => 'A anotação corrigida não é deste paciente.',
        ];
    }

    /** Converts the request into the note DTO. */
    public function toData(): ClinicalNoteData
    {
        return new ClinicalNoteData(
            body: trim($this->string('body')),
            appointmentId: $this->filled('appointment_id') ? $this->integer('appointment_id') : null,
            amendsId: $this->filled('amends_id') ? $this->integer('amends_id') : null,
        );
    }

    /** Patient of the route. */
    private function patient(): Patient
    {
        return $this->route('patient');
    }
}
