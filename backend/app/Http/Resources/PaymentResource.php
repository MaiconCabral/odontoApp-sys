<?php

namespace App\Http\Resources;

use App\Domains\Finance\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    public static $wrap = null;

    /**
     * A payment with who recorded it and, when voided, why.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'amountCents' => $this->amount_cents,
            'method' => $this->method->value,
            'methodLabel' => $this->method->label(),
            'paidAt' => $this->paid_at->toDateString(),
            'notes' => $this->notes,
            'recordedBy' => $this->recorder->name,
            'voided' => $this->voided_at !== null,
            'voidReason' => $this->void_reason,
            'voidedBy' => $this->voider?->name,
            'canVoid' => $this->voided_at === null && Gate::forUser($request->user())->allows('void', $this->resource),
        ];
    }
}
