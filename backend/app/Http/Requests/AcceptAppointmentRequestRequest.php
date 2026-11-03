<?php

namespace App\Http\Requests;

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AcceptAppointmentRequestRequest extends FormRequest
{
    /** Only admins and reception answer requests. */
    public function authorize(): bool
    {
        return Gate::allows('manage', Appointment::class);
    }

    /**
     * Validates the chosen room.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'room_id' => ['required', 'integer', Rule::exists(Room::class, 'id')->where('active', true)],
        ];
    }

    /**
     * Messages of the room choice.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'room_id.required' => 'Escolha a sala.',
            'room_id.exists' => 'Escolha uma sala em uso na clínica.',
        ];
    }
}
