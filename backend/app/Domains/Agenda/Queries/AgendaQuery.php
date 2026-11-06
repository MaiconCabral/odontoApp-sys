<?php

namespace App\Domains\Agenda\Queries;

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\BusinessHour;
use App\Domains\Agenda\Models\Procedure;
use App\Domains\Agenda\Models\Room;
use App\Domains\Agenda\Models\ScheduleBlock;
use App\Domains\Agenda\Support\DaySchedule;
use App\Domains\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class AgendaQuery
{
    private const DEFAULT_OPENING = '08:00';

    private const DEFAULT_CLOSING = '18:00';

    /** Receives the free slot counter. */
    public function __construct(private readonly AvailableSlots $slots) {}

    /**
     * Lists the forms need (procedures, professionals and rooms), without the events of a period.
     *
     * @return array<string, mixed>
     */
    public function options(User $viewer): array
    {
        $professionalId = $viewer->can('viewAll', Appointment::class) ? null : $viewer->id;

        return [
            'today' => today()->toDateString(),
            'procedures' => Procedure::options(),
            'professionals' => $this->professionals($professionalId),
            'rooms' => $this->rooms(),
        ];
    }

    /**
     * Builds the agenda of a period that the viewer is allowed to see.
     *
     * @return array<string, mixed>
     */
    public function for(User $viewer, CarbonImmutable $from, CarbonImmutable $to, ?int $requestedProfessionalId = null): array
    {
        // The viewer's role wins over any id sent by the client.
        $professionalId = $viewer->can('viewAll', Appointment::class) ? $requestedProfessionalId : $viewer->id;
        $professionals = $this->professionals($professionalId);

        return [
            'today' => today()->toDateString(),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'hours' => $this->hours(),
            'procedures' => Procedure::options(),
            'freeSlotsToday' => $this->slots->countFree(CarbonImmutable::today(), $professionals->pluck('id')->all()),
            'professionals' => $professionals,
            'rooms' => $this->rooms(),
            'events' => $this->events($from, $to, $professionalId),
            'blocks' => $this->blocks($from, $to, $professionalId),
        ];
    }

    /**
     * Rows of the day grid, every 30 minutes from the earliest opening to the latest closing.
     *
     * @return list<string>
     */
    private function hours(): array
    {
        $range = BusinessHour::query()->where('closed', false)
            ->toBase()
            ->selectRaw('MIN(opens_at) as opening, MAX(closes_at) as closing')
            ->first();
        $opening = BusinessHour::hhmm($range?->opening) ?? self::DEFAULT_OPENING;
        $closing = BusinessHour::hhmm($range?->closing) ?? self::DEFAULT_CLOSING;
        $hours = [];

        for ($time = CarbonImmutable::parse($opening); $time->format('H:i') < $closing; $time = $time->addMinutes(DaySchedule::SLOT_MINUTES)) {
            $hours[] = $time->format('H:i');
        }

        return $hours;
    }

    /** Active users who attend patients. */
    private function professionals(?int $professionalId): Collection
    {
        return User::query()
            ->where('attends_patients', true)
            ->where('active', true)
            ->when($professionalId, fn ($query) => $query->whereKey($professionalId))
            ->orderBy('name')
            ->get(['id', 'name', 'tone'])
            ->map(fn (User $professional) => [
                'id' => (string) $professional->id,
                'name' => $professional->name,
                'tone' => $professional->tone,
                'selected' => true,
            ]);
    }

    /** Rooms of the clinic. Inactive ones stay listed, so their old appointments still show up. */
    private function rooms(): Collection
    {
        return Room::query()
            ->orderByDesc('active')
            ->orderBy('name')
            ->get(['id', 'name', 'active'])
            ->map(fn (Room $room) => [
                'id' => (string) $room->id,
                'name' => $room->name,
                'active' => $room->active,
                'selected' => true,
            ]);
    }

    /** Appointments of the period, without cancelled and rejected ones. */
    private function events(CarbonImmutable $from, CarbonImmutable $to, ?int $professionalId): Collection
    {
        return Appointment::query()
            ->with(['patient:id,name', 'procedure:id,name'])
            ->withPaidCents()
            ->whereNotIn('status', [AppointmentStatus::Cancelled, AppointmentStatus::Rejected])
            ->whereBetween('starts_at', [$from, $to])
            ->when($professionalId, fn ($query) => $query->where('professional_id', $professionalId))
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Appointment $appointment) => [
                'id' => (string) $appointment->id,
                'patientName' => $appointment->patient->name,
                'date' => $appointment->starts_at->toDateString(),
                'hour' => $appointment->starts_at->format('H:i'),
                'endHour' => $appointment->ends_at->format('H:i'),
                'duration' => $appointment->minutes(),
                'procedure' => $appointment->procedure->label(),
                'procedureId' => (string) $appointment->procedure_id,
                'status' => $appointment->status->value,
                'statusLabel' => $appointment->status->label(),
                'source' => $appointment->source,
                'professionalId' => $appointment->professional_id ? (string) $appointment->professional_id : null,
                'roomId' => $appointment->room_id ? (string) $appointment->room_id : null,
                'paymentStatus' => $appointment->paymentStatus(),
            ]);
    }

    /** Holidays and days off of the period that matter to the viewer. */
    private function blocks(CarbonImmutable $from, CarbonImmutable $to, ?int $professionalId): Collection
    {
        return ScheduleBlock::query()
            ->overlapping($from, $to)
            ->when($professionalId, fn ($query) => $query->where(fn ($query) => $query->whereNull('professional_id')->orWhere('professional_id', $professionalId)))
            ->orderBy('starts_at')
            ->get()
            ->map(fn (ScheduleBlock $block) => [
                'id' => (string) $block->id,
                'professionalId' => $block->professional_id ? (string) $block->professional_id : null,
                'startsAt' => $block->starts_at->format('Y-m-d H:i'),
                'endsAt' => $block->ends_at->format('Y-m-d H:i'),
                'reason' => $block->reason,
            ]);
    }
}
