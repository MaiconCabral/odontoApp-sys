<?php

namespace App\Http\Requests\Concerns;

use App\Domains\Agenda\Models\Procedure;
use App\Domains\Agenda\Models\Room;
use App\Domains\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

trait HasAppointmentRules
{
    /** Procedure must exist and still be offered. */
    protected function activeProcedureRule(): Exists
    {
        return Rule::exists(Procedure::class, 'id')->where('active', true);
    }

    /** Room must exist and still be in use. */
    protected function activeRoomRule(): Exists
    {
        return Rule::exists(Room::class, 'id')->where('active', true);
    }

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
            'procedure_id' => ['required', 'integer', $this->activeProcedureRule()],
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
            'procedure_id.required' => 'Escolha o procedimento.',
            'procedure_id.exists' => 'Escolha um procedimento oferecido pela clínica.',
            'date.required' => 'Informe a data da consulta.',
            'date.date_format' => 'Informe a data da consulta.',
            'time.required' => 'Escolha o horário.',
            'time.date_format' => 'Escolha o horário.',
            'professional_id.required' => 'Escolha o profissional.',
            'professional_id.exists' => 'Escolha um profissional da clínica.',
            'room_id.required' => 'Escolha a sala.',
            'room_id.exists' => 'Escolha uma sala em uso na clínica.',
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
        return Procedure::query()->findOrFail($this->integer('procedure_id'));
    }
}
