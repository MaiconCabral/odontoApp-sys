<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class AgendaRequest extends FormRequest
{
    public const MAX_DAYS = 62;

    /**
     * Validates the period and the optional professional filter.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:from'],
            'professional_id' => ['sometimes', 'integer'],
        ];
    }

    /**
     * Refuses periods longer than two months.
     *
     * @return list<callable>
     */
    public function after(): array
    {
        return [function ($validator) {
            if ($validator->errors()->isEmpty() && $this->from()->diffInDays($this->to()) > self::MAX_DAYS) {
                $validator->errors()->add('to', 'Escolha um período de até 62 dias.');
            }
        }];
    }

    /** Professional asked by the client, if any. */
    public function professionalId(): ?int
    {
        return $this->filled('professional_id') ? $this->integer('professional_id') : null;
    }

    /** First day of the period, the current week by default. */
    public function from(): CarbonImmutable
    {
        return $this->filled('from')
            ? CarbonImmutable::createFromFormat('Y-m-d', $this->input('from'))->startOfDay()
            : CarbonImmutable::today()->startOfWeek();
    }

    /** Last day of the period, six weeks after the start by default. */
    public function to(): CarbonImmutable
    {
        return $this->filled('to')
            ? CarbonImmutable::createFromFormat('Y-m-d', $this->input('to'))->endOfDay()
            : $this->from()->addDays(41)->endOfDay();
    }
}
