<?php

namespace App\Http\Requests;

use App\Domains\Tenancy\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// The route already requires a platform admin (auth:platform).
class PlanRequest extends FormRequest
{
    /**
     * Validates a plan. The code is the stable id the frontend uses for the plan texts.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $plan = $this->route('plan');

        return [
            'code' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9-]+$/', Rule::unique(Plan::class, 'code')->ignore($plan?->id)],
            'name' => ['required', 'string', 'max:60'],
            'price_cents' => ['required', 'integer', 'min:0', 'max:100000000'],
            'max_professionals' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * Messages of the plan form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'Use letras minúsculas, números e hífen.',
            'code.unique' => 'Já existe um plano com este código.',
            'max_professionals.min' => 'Deixe vazio para não ter limite, ou informe ao menos 1.',
        ];
    }

    /**
     * Values ready to save.
     *
     * @return array{code: string, name: string, price_cents: int, max_professionals: int|null}
     */
    public function toAttributes(): array
    {
        return [
            'code' => $this->string('code')->trim()->toString(),
            'name' => $this->string('name')->trim()->toString(),
            'price_cents' => $this->integer('price_cents'),
            'max_professionals' => $this->filled('max_professionals') ? $this->integer('max_professionals') : null,
        ];
    }
}
