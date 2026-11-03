<?php

namespace App\Http\Requests;

use App\Domains\Agenda\Models\Procedure;
use App\Domains\Agenda\Support\DaySchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ProcedureRequest extends FormRequest
{
    /** Only admins change procedures. */
    public function authorize(): bool
    {
        $procedure = $this->route('procedure');

        return $procedure ? Gate::allows('update', $procedure) : Gate::allows('create', Procedure::class);
    }

    /**
     * Validates the procedure form.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'min:3', 'max:80',
                Rule::unique(Procedure::class, 'name')->where('active', true)->ignore($this->route('procedure')),
            ],
            'duration_minutes' => ['required', 'integer', 'min:30', 'max:240', 'multiple_of:'.DaySchedule::SLOT_MINUTES],
            'price_cents' => ['required', 'integer', 'min:0', 'max:10000000'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Messages of the procedure form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome do procedimento.',
            'name.min' => 'Informe o nome do procedimento.',
            'name.unique' => 'Já existe um procedimento ativo com este nome.',
            'duration_minutes.*' => 'Escolha uma duração entre 30 minutos e 4 horas, em intervalos de 30 minutos.',
            'price_cents.*' => 'Informe um preço válido.',
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
            'duration_minutes' => $this->integer('duration_minutes'),
            'price_cents' => $this->integer('price_cents'),
            'active' => $this->boolean('active', true),
        ];
    }
}
