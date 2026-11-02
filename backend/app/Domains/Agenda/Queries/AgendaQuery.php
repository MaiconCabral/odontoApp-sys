<?php

namespace App\Domains\Agenda\Queries;

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Room;
use App\Domains\Identity\Models\User;
use Illuminate\Support\Collection;

class AgendaQuery
{
    private const HOURS = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];

    private const PROCEDURES = ['Avaliação', 'Avaliação ortodôntica', 'Limpeza e prevenção', 'Restauração estética', 'Consulta de retorno'];

    private const MONTHS_BEFORE = 1;

    private const MONTHS_AFTER = 2;

    /**
     * Builds the agenda the viewer is allowed to see.
     *
     * @return array<string, mixed>
     */
    public function for(User $viewer, ?int $requestedProfessionalId = null): array
    {
        // The viewer's role wins over any id sent by the client.
        $professionalId = $viewer->can('viewAll', Appointment::class) ? $requestedProfessionalId : $viewer->id;
        $professionals = $this->professionals($professionalId);
        $events = $this->events($professionalId);

        return [
            'today' => today()->toDateString(),
            'hours' => self::HOURS,
            'procedures' => self::PROCEDURES,
            'freeSlotsToday' => $this->freeSlotsToday($professionals, $events),
            'professionals' => $professionals,
            'rooms' => $this->rooms(),
            'events' => $events,
        ];
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

    /** Rooms of the clinic. */
    private function rooms(): Collection
    {
        return Room::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Room $room) => [
                'id' => (string) $room->id,
                'name' => $room->name,
                'selected' => true,
            ]);
    }

    /** Appointments in the loaded window, without cancelled ones. */
    private function events(?int $professionalId): Collection
    {
        return Appointment::query()
            ->with('patient:id,name')
            ->where('status', '!=', Appointment::STATUS_CANCELLED)
            ->whereBetween('starts_at', [
                today()->subMonthsNoOverflow(self::MONTHS_BEFORE),
                today()->addMonthsNoOverflow(self::MONTHS_AFTER)->endOfDay(),
            ])
            ->when($professionalId, fn ($query) => $query->where('professional_id', $professionalId))
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Appointment $appointment) => [
                'id' => (string) $appointment->id,
                'patientName' => $appointment->patient->name,
                'date' => $appointment->starts_at->toDateString(),
                'hour' => $appointment->starts_at->format('H:i'),
                'procedure' => $appointment->procedure,
                'tone' => $appointment->tone,
                'professionalId' => (string) $appointment->professional_id,
                'roomId' => (string) $appointment->room_id,
            ]);
    }

    /** Free hours today for the listed professionals. */
    private function freeSlotsToday(Collection $professionals, Collection $events): int
    {
        $booked = $events->where('date', today()->toDateString())->count();

        return max(0, count(self::HOURS) * $professionals->count() - $booked);
    }
}
