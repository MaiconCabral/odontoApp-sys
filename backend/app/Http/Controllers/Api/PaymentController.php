<?php

namespace App\Http\Controllers\Api;

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Finance\Actions\RecordPayment;
use App\Domains\Finance\Actions\VoidPayment;
use App\Domains\Finance\Models\Payment;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordPaymentRequest;
use App\Http\Resources\PaymentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    /** Payments of an appointment, voided ones included, with what is left to pay. */
    public function index(Appointment $appointment): JsonResponse
    {
        Gate::authorize('manage', Payment::class);

        return $this->summary($appointment);
    }

    /** Records a payment and returns the new summary of the appointment. */
    public function store(RecordPaymentRequest $request, Appointment $appointment, RecordPayment $record): JsonResponse
    {
        $record->handle($appointment, $request->toData(), $request->user());

        return $this->summary($appointment->fresh())->setStatusCode(201);
    }

    /** Voids a payment with a reason and returns the new summary of its appointment. */
    public function void(Request $request, Payment $payment, VoidPayment $void): JsonResponse
    {
        Gate::authorize('void', $payment);

        $reason = $request->validate(
            ['reason' => ['required', 'string', 'min:3', 'max:255']],
            ['reason.required' => 'Informe o motivo do estorno.', 'reason.min' => 'Informe o motivo do estorno.'],
        )['reason'];

        $void->handle($payment, trim($reason), $request->user());

        return $this->summary($payment->appointment);
    }

    /** Price, paid, balance, status and the list of payments of an appointment. */
    private function summary(Appointment $appointment): JsonResponse
    {
        $payments = $appointment->payments()->with(['recorder:id,name', 'voider:id,name'])->get();
        $paid = (int) $payments->whereNull('voided_at')->sum('amount_cents');
        $appointment->setRawAttributes([...$appointment->getAttributes(), 'paid_cents' => $paid], true);

        return response()->json([
            'priceCents' => $appointment->price_cents,
            'paidCents' => $paid,
            'balanceCents' => max(0, $appointment->price_cents - $paid),
            'paymentStatus' => $appointment->paymentStatus(),
            'payments' => PaymentResource::collection($payments),
        ]);
    }
}
