<?php

namespace App\Http\Requests;

use App\Domains\Platform\DTOs\ClinicAccountData;
use App\Domains\Tenancy\Models\Plan;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// The route already requires a platform admin (auth:platform).
class UpdateClinicAccountRequest extends FormRequest
{
    /**
     * Validates the account of a clinic.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', Rule::exists(Plan::class, 'id')],
            'trial_ends_at' => ['nullable', 'date_format:Y-m-d'],
            'suspended' => ['required', 'boolean'],
        ];
    }

    /**
     * Messages of the account form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plan_id.required' => 'Escolha o plano.',
            'plan_id.exists' => 'Escolha um plano existente.',
        ];
    }

    /** Converts the request into the account DTO. */
    public function toData(): ClinicAccountData
    {
        return new ClinicAccountData(
            planId: $this->integer('plan_id'),
            trialEndsAt: $this->filled('trial_ends_at') ? CarbonImmutable::parse($this->string('trial_ends_at')) : null,
            suspended: $this->boolean('suspended'),
        );
    }
}
