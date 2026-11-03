<?php

namespace App\Http\Controllers\Api;

use App\Domains\Agenda\Actions\ChangeAppointmentStatus;
use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Notifications\PatientNotifier;
use App\Domains\Notifications\Support\PatientLinks;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

// Pages a patient opens from an e-mail, without an account. The token is the only key.
class PatientLinkController extends Controller
{
    public const CONFIRM_REASON = 'Confirmado pelo paciente pelo link.';

    public const CANCEL_REASON = 'Cancelado pelo paciente pelo link.';

    /** Receives the link signer. */
    public function __construct(private readonly PatientLinks $links) {}

    /** Returns what the patient needs to see about the appointment. */
    public function show(string $token): JsonResponse
    {
        return response()->json($this->summary($this->resolve($token)));
    }

    /** Confirms the attendance. Confirming twice keeps it confirmed. */
    public function confirm(string $token, ChangeAppointmentStatus $change): JsonResponse
    {
        $appointment = $this->resolve($token);

        if ($appointment->status === AppointmentStatus::Scheduled) {
            $appointment = $change->handle($appointment, AppointmentStatus::Confirmed, self::CONFIRM_REASON, null);
        }

        return response()->json($this->summary($appointment));
    }

    /** Cancels the appointment and frees the time. */
    public function cancel(string $token, ChangeAppointmentStatus $change): JsonResponse
    {
        $appointment = $change->handle($this->resolve($token), AppointmentStatus::Cancelled, self::CANCEL_REASON, null);

        return response()->json($this->summary($appointment));
    }

    /** Appointment of the token, or a 404 for an unknown or expired link. */
    private function resolve(string $token): Appointment
    {
        $appointment = $this->links->resolve($token);

        // A JSON response of its own, because the generic 404 message would hide this one.
        abort_if($appointment === null, response()->json(['message' => 'Este link é inválido ou expirou.'], 404));

        return $appointment;
    }

    /**
     * Appointment data safe to show to whoever holds the link.
     *
     * @return array<string, mixed>
     */
    private function summary(Appointment $appointment): array
    {
        $appointment->loadMissing(['procedure', 'professional']);
        $clinic = tenant();

        return [
            'clinic' => ['name' => $clinic->name, 'address' => $clinic->address, 'phone' => $clinic->phone],
            'when' => PatientNotifier::when($appointment->starts_at),
            'procedure' => $appointment->procedure->name,
            'professional' => $appointment->professional?->name,
            'status' => $appointment->status->value,
            'statusLabel' => $appointment->status->label(),
            'canConfirm' => $appointment->status === AppointmentStatus::Scheduled,
            'canCancel' => in_array($appointment->status, [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed], true),
        ];
    }
}
