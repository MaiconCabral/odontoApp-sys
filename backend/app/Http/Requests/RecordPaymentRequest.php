<?php

namespace App\Http\Requests;

use App\Domains\Finance\DTOs\PaymentData;
use App\Domains\Finance\Enums\PaymentMethod;
use App\Domains\Finance\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class RecordPaymentRequest extends FormRequest
{
    /** Admins and reception record payments. */
    public function authorize(): bool
    {
        return Gate::allows('manage', Payment::class);
    }

    /**
     * Validates the payment. It cannot be dated in the future.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'amount_cents' => ['required', 'integer', 'min:1', 'max:10000000'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'paid_at' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
            'allow_over' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Messages of the payment form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount_cents.required' => 'Informe o valor.',
            'amount_cents.min' => 'Informe um valor maior que zero.',
            'method.required' => 'Escolha a forma de pagamento.',
            'paid_at.before_or_equal' => 'A data do pagamento não pode estar no futuro.',
        ];
    }

    /** Converts the request into the payment DTO; without a date, today. */
    public function toData(): PaymentData
    {
        return new PaymentData(
            amountCents: $this->integer('amount_cents'),
            method: PaymentMethod::from($this->string('method')->toString()),
            paidAt: $this->filled('paid_at') ? CarbonImmutable::parse($this->string('paid_at')) : CarbonImmutable::today(),
            notes: $this->filled('notes') ? trim($this->string('notes')) : null,
            allowOver: $this->boolean('allow_over'),
        );
    }
}
