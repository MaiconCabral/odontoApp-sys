<?php

namespace App\Domains\Finance\Actions;

use App\Domains\Finance\Models\Payment;
use App\Domains\Identity\Models\User;
use Illuminate\Validation\ValidationException;

class VoidPayment
{
    /** Voids a payment with a reason. It stops counting but stays in the history. */
    public function handle(Payment $payment, string $reason, User $author): Payment
    {
        if ($payment->voided_at) {
            throw ValidationException::withMessages(['payment' => 'Este pagamento já foi estornado.']);
        }

        $payment->update([
            'voided_at' => now(),
            'voided_by' => $author->id,
            'void_reason' => $reason,
        ]);

        return $payment;
    }
}
