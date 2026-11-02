<?php

namespace App\Http\Resources;

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\AppointmentStatusChange;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * @mixin Appointment
 */
class AppointmentResource extends JsonResource
{
    public static $wrap = null;

    private const ACTIONS = [
        'confirmed' => 'confirm',
        'completed' => 'complete',
        'no_show' => 'no_show',
        'cancelled' => 'cancel',
        'rejected' => 'reject',
    ];

    /**
     * Appointment with patient, history and the actions the viewer may take.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'procedure' => $this->procedure->value,
            'procedureLabel' => $this->procedure->label(),
            'duration' => $this->procedure->minutes(),
            'date' => $this->starts_at->toDateString(),
            'hour' => $this->starts_at->format('H:i'),
            'endHour' => $this->ends_at->format('H:i'),
            'source' => $this->source,
            'notes' => $this->notes,
            'cancelReason' => $this->cancel_reason,
            'requestedAt' => $this->created_at?->toIso8601String(),
            'patient' => $this->whenLoaded('patient', fn () => [
                'id' => (string) $this->patient->id,
                'name' => $this->patient->name,
                'phone' => $this->patient->phone,
                'email' => $this->patient->email,
            ]),
            'professional' => $this->whenLoaded('professional', fn () => $this->professional ? [
                'id' => (string) $this->professional->id,
                'name' => $this->professional->name,
            ] : null),
            'room' => $this->whenLoaded('room', fn () => $this->room ? [
                'id' => (string) $this->room->id,
                'name' => $this->room->name,
            ] : null),
            'history' => $this->whenLoaded('statusChanges', fn () => $this->statusChanges->map(fn (AppointmentStatusChange $change) => [
                'from' => $change->from_status?->value,
                'fromLabel' => $change->from_status?->label(),
                'to' => $change->to_status->value,
                'toLabel' => $change->to_status->label(),
                'reason' => $change->reason,
                'author' => $change->author?->name ?? 'Paciente (pedido online)',
                'at' => $change->created_at->toIso8601String(),
            ])->all()),
            'actions' => $this->actionsFor($request),
        ];
    }

    /**
     * Actions the signed-in user may take on this appointment now.
     *
     * @return list<string>
     */
    private function actionsFor(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return [];
        }

        $actions = [];

        if ($this->status === AppointmentStatus::Requested && Gate::forUser($user)->allows('manage', Appointment::class)) {
            $actions[] = 'accept';
        }

        foreach ($this->status->nextStatuses() as $next) {
            $action = self::ACTIONS[$next->value] ?? null;
            $tooEarly = $next->requiresStart() && $this->starts_at > now();

            if ($action && ! $tooEarly && Gate::forUser($user)->allows('changeStatus', [$this->resource, $next])) {
                $actions[] = $action;
            }
        }

        $canMove = in_array($this->status, [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed], true);

        if ($canMove && Gate::forUser($user)->allows('manage', Appointment::class)) {
            $actions[] = 'reschedule';
        }

        return $actions;
    }
}
