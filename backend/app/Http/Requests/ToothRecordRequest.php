<?php

namespace App\Http\Requests;

use App\Domains\Clinical\DTOs\ToothRecordData;
use App\Domains\Clinical\Enums\ToothCondition;
use App\Domains\Clinical\Enums\ToothSurface;
use App\Domains\Clinical\Support\Teeth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ToothRecordRequest extends FormRequest
{
    /** Who sees the medical record of the patient may change the odontogram. */
    public function authorize(): bool
    {
        return Gate::allows('writeRecord', $this->route('patient'));
    }

    /**
     * Caries and restorations need a surface; conditions of the whole tooth take none.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'tooth' => ['required', 'integer', Rule::in(Teeth::all())],
            'condition' => ['required', Rule::enum(ToothCondition::class)],
            'surface' => [
                'nullable',
                Rule::enum(ToothSurface::class),
                Rule::requiredIf(fn () => in_array($this->input('condition'), [ToothCondition::Caries->value, ToothCondition::Restoration->value], true)),
            ],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Messages of the odontogram form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tooth.in' => 'Escolha um dente válido.',
            'surface.required' => 'Escolha a face do dente.',
        ];
    }

    /** Converts the request into the tooth DTO. */
    public function toData(): ToothRecordData
    {
        return new ToothRecordData(
            tooth: $this->integer('tooth'),
            surface: $this->filled('surface') ? ToothSurface::from($this->string('surface')->toString()) : null,
            condition: ToothCondition::from($this->string('condition')->toString()),
            notes: $this->filled('notes') ? trim($this->string('notes')) : null,
        );
    }
}
