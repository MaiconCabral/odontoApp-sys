<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasAppointmentRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ScheduleBlockRequest extends FormRequest
{
    use HasAppointmentRules;

    /** Only admins and reception register holidays and days off. */
    public function authorize(): bool
    {
        return Gate::allows('manageBlocks');
    }

    /**
     * Validates the block.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'professional_id' => ['nullable', 'integer', $this->attendingProfessionalRule()],
            'starts_at' => ['required', 'date_format:Y-m-d H:i'],
            'ends_at' => ['required', 'date_format:Y-m-d H:i', 'after:starts_at'],
            'reason' => ['required', 'string', 'min:3', 'max:120'],
        ];
    }

    /**
     * Messages of the block form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'starts_at.required' => 'Informe o início.',
            'starts_at.date_format' => 'Informe o início.',
            'ends_at.required' => 'Informe o fim.',
            'ends_at.date_format' => 'Informe o fim.',
            'ends_at.after' => 'O fim precisa ser depois do início.',
            'reason.required' => 'Informe o motivo, como "Feriado" ou "Folga".',
            'reason.min' => 'Informe o motivo, como "Feriado" ou "Folga".',
            'professional_id.exists' => 'Escolha um profissional da clínica.',
        ];
    }
}
