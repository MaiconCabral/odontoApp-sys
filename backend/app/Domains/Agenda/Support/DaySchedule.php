<?php

namespace App\Domains\Agenda\Support;

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\BusinessHour;
use App\Domains\Agenda\Models\ScheduleBlock;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

// Everything that decides whether a time is free on one day, loaded with three queries.
class DaySchedule
{
    public const SLOT_MINUTES = 30;

    /**
     * Keeps the opening hours, blocks and appointments of the day.
     *
     * @param  Collection<int, ScheduleBlock>  $blocks
     * @param  Collection<int, Appointment>  $appointments
     */
    public function __construct(
        public readonly CarbonImmutable $date,
        private readonly ?BusinessHour $hours,
        private readonly Collection $blocks,
        private readonly Collection $appointments,
    ) {}

    /** Loads the day, leaving out one appointment when it is being moved. */
    public static function load(CarbonInterface $date, ?int $ignoreAppointmentId = null): self
    {
        $day = CarbonImmutable::instance($date)->startOfDay();
        $nextDay = $day->addDay();

        return new self(
            $day,
            BusinessHour::query()->where('weekday', $day->dayOfWeekIso)->first(),
            ScheduleBlock::query()->overlapping($day, $nextDay)->get(),
            Appointment::query()
                ->occupying()
                ->overlapping($day, $nextDay)
                ->when($ignoreAppointmentId, fn ($query) => $query->whereKeyNot($ignoreAppointmentId))
                ->get(['id', 'professional_id', 'room_id', 'starts_at', 'ends_at']),
        );
    }

    /** Tells whether the clinic opens on this day. */
    public function isOpen(): bool
    {
        return $this->hours !== null && ! $this->hours->closed && $this->hours->opens_at && $this->hours->closes_at;
    }

    /**
     * Start of every slot between opening and closing.
     *
     * @return list<CarbonImmutable>
     */
    public function slotStarts(): array
    {
        if (! $this->isOpen()) {
            return [];
        }

        $slots = [];

        for ($time = $this->at($this->hours->opens_at); $time < $this->at($this->hours->closes_at); $time = $time->addMinutes(self::SLOT_MINUTES)) {
            $slots[] = $time;
        }

        return $slots;
    }

    /**
     * Why the interval cannot be booked, by field. Empty when it is free.
     *
     * @return array<string, string>
     */
    public function problems(CarbonImmutable $start, int $minutes, ?int $professionalId, ?int $roomId): array
    {
        $end = $start->addMinutes($minutes);

        if ($start->minute % self::SLOT_MINUTES !== 0) {
            return ['starts_at' => 'Escolha um horário em intervalos de 30 minutos.'];
        }

        $clinicProblem = $this->clinicProblem($start, $end);

        if ($clinicProblem) {
            return ['starts_at' => $clinicProblem];
        }

        $problems = [];

        if ($professionalId && $problem = $this->professionalProblem($start, $end, $professionalId)) {
            $problems['professional_id'] = $problem;
        }

        if ($roomId && $this->overlaps($start, $end)->contains('room_id', $roomId)) {
            $problems['room_id'] = 'Esta sala já está ocupada neste horário.';
        }

        return $problems;
    }

    /** Opening hours, lunch break and clinic closures. */
    private function clinicProblem(CarbonImmutable $start, CarbonImmutable $end): ?string
    {
        if (! $this->isOpen()) {
            return 'A clínica não atende neste dia.';
        }

        if ($start < $this->at($this->hours->opens_at) || $end > $this->at($this->hours->closes_at)) {
            return 'Fora do horário de funcionamento da clínica.';
        }

        if ($this->hours->break_starts_at && $this->hours->break_ends_at
            && $start < $this->at($this->hours->break_ends_at) && $end > $this->at($this->hours->break_starts_at)) {
            return 'Este horário cai no intervalo de almoço da clínica.';
        }

        $closure = $this->blocksAt($start, $end)->first(fn (ScheduleBlock $block) => $block->professional_id === null);

        return $closure ? "A clínica está fechada neste horário: {$closure->reason}." : null;
    }

    /** Leave and other appointments of the professional. */
    private function professionalProblem(CarbonImmutable $start, CarbonImmutable $end, int $professionalId): ?string
    {
        $leave = $this->blocksAt($start, $end)->first(fn (ScheduleBlock $block) => $block->professional_id === $professionalId);

        if ($leave) {
            return "O profissional não atende neste horário: {$leave->reason}.";
        }

        return $this->overlaps($start, $end)->contains('professional_id', $professionalId)
            ? 'Este profissional já tem consulta neste horário.'
            : null;
    }

    /**
     * Blocks that cross the interval.
     *
     * @return Collection<int, ScheduleBlock>
     */
    private function blocksAt(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return $this->blocks->filter(fn (ScheduleBlock $block) => $block->starts_at < $end && $block->ends_at > $start);
    }

    /**
     * Appointments that cross the interval.
     *
     * @return Collection<int, Appointment>
     */
    private function overlaps(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return $this->appointments->filter(fn (Appointment $appointment) => $appointment->starts_at < $end && $appointment->ends_at > $start);
    }

    /** The day at a time stored as HH:MM or HH:MM:SS. */
    private function at(string $time): CarbonImmutable
    {
        return $this->date->setTimeFromTimeString($time);
    }
}
