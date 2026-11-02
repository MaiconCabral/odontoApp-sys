<?php

namespace App\Http\Requests;

use App\Domains\Agenda\Enums\AppointmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ChangeAppointmentStatusRequest extends FormRequest
{
    /** Checks the role against the target status. */
    public function authorize(): bool
    {
        $status = AppointmentStatus::tryFrom((string) $this->input('status'));

        return $status === null || Gate::allows('changeStatus', [$this->route('appointment'), $status]);
    }

    /**
     * Validates the status change.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(AppointmentStatus::class)],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Messages of the status change.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Escolha o novo status.',
            'status.enum' => 'Escolha o novo status.',
        ];
    }

    /** Target status. */
    public function status(): AppointmentStatus
    {
        return AppointmentStatus::from($this->input('status'));
    }
}
