<?php

namespace App\Domains\Finance\Actions;

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Finance\DTOs\PaymentData;
use App\Domains\Finance\Models\Payment;
use App\Domains\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordPayment
{
    /** Statuses whose appointment may not be charged. */
    private const UNPAYABLE = [AppointmentStatus::Requested, AppointmentStatus::Cancelled, AppointmentStatus::Rejected];

    /** Records a payment, partial or full, without going over the price unless allowed. */
    public function handle(Appointment $appointment, PaymentData $data, User $recorder): Payment
    {
        return DB::transaction(function () use ($appointment, $data, $recorder) {
            // Two people may charge the same appointment at once; the lock makes the second see the first.
            $appointment = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if (in_array($appointment->status, self::UNPAYABLE, true)) {
                throw ValidationException::withMessages([
                    'appointment' => "Não é possível registrar pagamento de consulta {$appointment->status->label()}.",
                ]);
            }

            $paid = (int) $appointment->payments()->valid()->sum('amount_cents');

            if (! $data->allowOver && $paid + $data->amountCents > $appointment->price_cents) {
                throw ValidationException::withMessages([
                    'amount_cents' => 'O valor passa do preço da consulta. Faltam '.self::money(max(0, $appointment->price_cents - $paid)).'.',
                ]);
            }

            return Payment::create([
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'amount_cents' => $data->amountCents,
                'method' => $data->method,
                'paid_at' => $data->paidAt,
                'notes' => $data->notes,
                'recorded_by' => $recorder->id,
            ]);
        });
    }

    /** Price as "R$ 150,00". */
    private static function money(int $cents): string
    {
        return 'R$ '.number_format($cents / 100, 2, ',', '.');
    }
}
