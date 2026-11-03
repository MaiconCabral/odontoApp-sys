<?php

namespace App\Http\Requests;

use App\Domains\Agenda\DTOs\WaitlistData;
use App\Domains\Agenda\Enums\WaitlistPeriod;
use App\Domains\Agenda\Enums\WaitlistStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Agenda\Models\Procedure;
use App\Domains\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class WaitlistRequest extends FormRequest
{
    /** Longest range of days one entry may wait for. */
    private const MAX_DAYS = 180;

    /** Admins and reception keep the waiting list. */
    public function authorize(): bool
    {
        return Gate::allows('manage', Appointment::class);
    }

    /**
     * Validates the entry. The status is only changed on edits.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        // A malformed date fails on its own rule; the limit then counts from today.
        $from = rescue(fn () => $this->date('from_date', 'Y-m-d'), report: false) ?? now();

        return [
            'patient_id' => ['required', 'integer', Rule::exists(Patient::class, 'id')],
            'procedure_id' => ['required', 'integer', Rule::exists(Procedure::class, 'id')->where('active', true)],
            'professional_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')->where('attends_patients', true)->where('active', true)],
            'from_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date', 'before_or_equal:'.$from->addDays(self::MAX_DAYS)->toDateString()],
            'period' => ['required', Rule::enum(WaitlistPeriod::class)],
            'notes' => ['nullable', 'string', 'max:500'],
            'status' => [$this->isMethod('PUT') ? 'sometimes' : 'prohibited', Rule::enum(WaitlistStatus::class)],
        ];
    }

    /**
     * Messages of the waiting list form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'patient_id.required' => 'Escolha o paciente.',
            'procedure_id.required' => 'Escolha o procedimento.',
            'procedure_id.exists' => 'Escolha um procedimento ativo.',
            'professional_id.exists' => 'Escolha um profissional que atende.',
            'from_date.after_or_equal' => 'A data inicial não pode estar no passado.',
            'to_date.after_or_equal' => 'A data final precisa ser igual ou depois da inicial.',
            'to_date.before_or_equal' => 'O período pode ter até 180 dias.',
        ];
    }

    /** Converts the request into the waiting list DTO. */
    public function toData(): WaitlistData
    {
        return new WaitlistData(
            patientId: $this->integer('patient_id'),
            procedureId: $this->integer('procedure_id'),
            professionalId: $this->filled('professional_id') ? $this->integer('professional_id') : null,
            fromDate: CarbonImmutable::parse($this->string('from_date')),
            toDate: CarbonImmutable::parse($this->string('to_date')),
            period: WaitlistPeriod::from($this->string('period')->toString()),
            notes: $this->filled('notes') ? trim($this->string('notes')) : null,
            status: $this->filled('status') ? WaitlistStatus::from($this->string('status')->toString()) : null,
        );
    }
}
