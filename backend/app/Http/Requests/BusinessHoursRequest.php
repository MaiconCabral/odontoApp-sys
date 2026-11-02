<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class BusinessHoursRequest extends FormRequest
{
    /** Only admins change the opening hours. */
    public function authorize(): bool
    {
        return Gate::allows('updateBusinessHours');
    }

    /**
     * Validates the seven weekdays.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'days' => ['required', 'array', 'size:7'],
            'days.*.weekday' => ['required', 'integer', 'between:1,7', 'distinct'],
            'days.*.closed' => ['required', 'boolean'],
            'days.*.opens_at' => ['nullable', 'required_if:days.*.closed,false', 'date_format:H:i'],
            'days.*.closes_at' => ['nullable', 'required_if:days.*.closed,false', 'date_format:H:i'],
            'days.*.break_starts_at' => ['nullable', 'date_format:H:i', 'required_with:days.*.break_ends_at'],
            'days.*.break_ends_at' => ['nullable', 'date_format:H:i', 'required_with:days.*.break_starts_at'],
        ];
    }

    /**
     * Messages of the opening hours.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'days.*.opens_at.required_if' => 'Informe a abertura.',
            'days.*.closes_at.required_if' => 'Informe o fechamento.',
            'days.*.*.date_format' => 'Use o formato 08:00.',
            'days.*.break_starts_at.required_with' => 'Informe o início do intervalo.',
            'days.*.break_ends_at.required_with' => 'Informe o fim do intervalo.',
        ];
    }

    /**
     * Checks the order of the times on each open day.
     *
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            foreach ((array) $this->input('days', []) as $index => $day) {
                if (! empty($day['closed']) || empty($day['opens_at']) || empty($day['closes_at'])) {
                    continue;
                }

                if ($day['opens_at'] >= $day['closes_at']) {
                    $validator->errors()->add("days.{$index}.closes_at", 'O fechamento precisa ser depois da abertura.');
                }

                if (! empty($day['break_starts_at']) && ! empty($day['break_ends_at'])
                    && ($day['break_starts_at'] >= $day['break_ends_at'] || $day['break_starts_at'] < $day['opens_at'] || $day['break_ends_at'] > $day['closes_at'])) {
                    $validator->errors()->add("days.{$index}.break_ends_at", 'O intervalo precisa ficar dentro do expediente.');
                }
            }
        }];
    }
}
