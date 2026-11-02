<?php

namespace App\Http\Requests\Concerns;

use App\Domains\Agenda\Enums\Procedure;
use App\Domains\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

trait HasAppointmentRules
{
    /** Professional must be an active user who attends patients. */
    protected function attendingProfessionalRule(): Exists
    {
        return Rule::exists(User::class, 'id')->where('attends_patients', true)->where('active', true);
    }

    /**
     * Rules for the procedure and the start date and time.
     *
     * @return array<string, list<mixed>>
     */
    protected function timeRules(): array
    {
        return [
            'procedure' => ['required', Rule::enum(Procedure::class)],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
        ];
    }

    /**
     * Messages for the time rules.
     *
     * @return array<string, string>
     */
    protected function timeMessages(): array
    {
        return [
            'procedure.required' => 'Escolha o procedimento.',
            'procedure.enum' => 'Escolha o procedimento.',
            'date.required' => 'Informe a data da consulta.',
            'date.date_format' => 'Informe a data da consulta.',
            'time.required' => 'Escolha o horário.',
            'time.date_format' => 'Escolha o horário.',
            'professional_id.required' => 'Escolha o profissional.',
            'professional_id.exists' => 'Escolha um profissional da clínica.',
            'room_id.required' => 'Escolha a sala.',
            'room_id.exists' => 'Escolha uma sala da clínica.',
            'patient_id.required' => 'Selecione um paciente cadastrado.',
            'patient_id.exists' => 'Selecione um paciente cadastrado.',
        ];
    }

    /** Start of the appointment in the clinic time zone. */
    public function startsAt(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d H:i', $this->input('date').' '.$this->input('time'));
    }

    /** Procedure chosen in the form. */
    public function procedure(): Procedure
    {
        return Procedure::from($this->input('procedure'));
    }
}
