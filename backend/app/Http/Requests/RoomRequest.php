<?php

namespace App\Http\Requests;

use App\Domains\Agenda\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class RoomRequest extends FormRequest
{
    /** Only admins change rooms. */
    public function authorize(): bool
    {
        $room = $this->route('room');

        return $room ? Gate::allows('update', $room) : Gate::allows('create', Room::class);
    }

    /**
     * Validates the room form.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'min:2', 'max:80',
                Rule::unique(Room::class, 'name')->where('active', true)->ignore($this->route('room')),
            ],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Messages of the room form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome da sala.',
            'name.min' => 'Informe o nome da sala.',
            'name.unique' => 'Já existe uma sala ativa com este nome.',
        ];
    }

    /**
     * Validated data with the default status.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'name' => trim($this->string('name')),
            'active' => $this->boolean('active', true),
        ];
    }
}
